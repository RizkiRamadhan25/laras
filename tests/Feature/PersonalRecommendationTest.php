<?php

namespace Tests\Feature;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Enums\TransactionSource;
use App\Models\Account;
use App\Models\Activity;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\PersonalRecommendationService;
use App\Services\SubscriptionService;
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_empty_recommendation_page(): void
    {
        [$user] = $this->context();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'recommendations.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Fokus pada hal yang paling penting.'
            )
            ->assertSee(
                'Semuanya terkendali'
            );
    }

    public function test_failed_billing_becomes_highest_recommendation(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [
            $user,
            $account,
            $category,
        ] = $this->context();

        Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Tugas mendesak',

                'status' =>
                    ActivityStatus::Planned,

                'due_at' =>
                    $reference->addHour(),
            ]);

        $subscription =
            $this->subscriptionFor(
                user: $user,
                account: $account,
                category: $category
            );

        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $billing->forceFill([
            'status' =>
                SubscriptionBillingStatus::Failed,

            'attempted_at' => now(),

            'failure_reason' =>
                'Saldo rekening tidak mencukupi.',

            'metadata' => [
                'attempt_count' => 1,
            ],
        ])->save();

        $recommendations = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $this->assertSame(
            'billing_failed',
            $recommendations['items']
                ->first()['kind']
        );

        $this->assertSame(
            100,
            $recommendations['items']
                ->first()['score']
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'recommendations.index'
                )
            )
            ->assertOk()
            ->assertSeeInOrder([
                'Tagihan Spotify gagal diproses',
                'Prioritaskan Tugas mendesak',
            ]);
    }

    public function test_overdue_activity_is_recommended(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 12:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [
            $user,
        ] = $this->context();

        Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,

                'title' =>
                    'Mengumpulkan laporan',

                'priority' =>
                    ActivityPriority::Urgent,

                'status' =>
                    ActivityStatus::Planned,

                'due_at' =>
                    $reference->subDay(),
            ]);

        $recommendations = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $activityItem =
            $recommendations['items']
                ->firstWhere(
                    'kind',
                    'activity'
                );

        $this->assertNotNull(
            $activityItem
        );

        $this->assertSame(
            'danger',
            $activityItem['severity']
        );

        $this->assertStringContainsString(
            'terlambat',
            strtolower(
                $activityItem['title']
            )
        );
    }

    public function test_upcoming_subscription_is_recommended(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription =
            $this->subscriptionFor(
                user: $user,
                account: $account,
                category: $category,
                data: [
                    'next_billing_on' =>
                        '2026-08-12',
                ]
            );

        $recommendations = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $item = $recommendations['items']
            ->firstWhere(
                'kind',
                'subscription_due'
            );

        $this->assertNotNull($item);

        $this->assertStringContainsString(
            $subscription->name,
            $item['title']
        );

        $this->assertSame(
            'warning',
            $item['severity']
        );
    }

    public function test_expense_increase_generates_financial_recommendation(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-15 12:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [
            $user,
            $account,
            $category,
        ] = $this->context(
            balance: '5000000.00'
        );

        $this->postExpense(
            user: $user,
            account: $account,
            category: $category,
            amount: '100000',
            occurredAt:
                '2026-07-10 10:00:00'
        );

        $this->postExpense(
            user: $user,
            account: $account,
            category: $category,
            amount: '250000',
            occurredAt:
                '2026-08-10 10:00:00'
        );

        $recommendations = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $expenseItem =
            $recommendations['items']
                ->firstWhere(
                    'kind',
                    'expense_increase'
                );

        $categoryItem =
            $recommendations['items']
                ->firstWhere(
                    'kind',
                    'dominant_category'
                );

        $this->assertNotNull(
            $expenseItem
        );

        $this->assertNotNull(
            $categoryItem
        );

        $this->assertStringContainsString(
            'naik',
            strtolower(
                $expenseItem['title']
            )
        );

        $this->assertStringContainsString(
            'Makanan',
            $categoryItem['title']
        );
    }

    public function test_recommendations_do_not_include_another_users_data(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        [
            $user,
        ] = $this->context();

        [
            $otherUser,
            $otherAccount,
            $otherCategory,
        ] = $this->context();

        $subscription =
            $this->subscriptionFor(
                user: $otherUser,
                account: $otherAccount,
                category: $otherCategory,
                data: [
                    'name' =>
                        'Langganan pengguna lain',
                ]
            );

        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $billing->forceFill([
            'status' =>
                SubscriptionBillingStatus::Failed,

            'failure_reason' =>
                'Tagihan pengguna lain gagal.',

            'attempted_at' => now(),
        ])->save();

        $recommendations = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $containsOtherUserData =
            $recommendations['items']
                ->contains(
                    fn (array $item): bool =>
                        str_contains(
                            $item['title'],
                            'Langganan pengguna lain'
                        )
                );

        $this->assertFalse(
            $containsOtherUserData
        );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Account,
     *     2: FinanceCategory
     * }
     */
    private function context(
        string $balance = '1000000.00'
    ): array {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'currency_code' => 'IDR',
            'initial_balance' => $balance,
            'cached_balance' => $balance,
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
                'name' => 'Makanan',

                'flow_type' =>
                    FinanceFlowType::Expense,

                'is_active' => true,
            ]);

        return [
            $user,
            $account,
            $category,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function subscriptionFor(
        User $user,
        Account $account,
        FinanceCategory $category,
        array $data = []
    ): Subscription {
        return app(
            SubscriptionService::class
        )->create(
            user: $user,

            data: array_merge(
                [
                    'account_id' =>
                        $account->id,

                    'finance_category_id' =>
                        $category->id,

                    'name' => 'Spotify',
                    'provider' => 'Spotify',
                    'amount' => '59000',

                    'interval_unit' =>
                        'month',

                    'interval_count' => 1,

                    'started_on' =>
                        '2026-08-10',

                    'next_billing_on' =>
                        '2026-08-10',

                    'end_on' => null,
                    'billing_time' => '08:00',
                    'auto_post' => true,

                    'reminder_days' => [
                        3,
                        1,
                    ],
                ],
                $data
            )
        );
    }

    private function postExpense(
        User $user,
        Account $account,
        FinanceCategory $category,
        string $amount,
        string $occurredAt
    ): void {
        app(
            TransactionPostingService::class
        )->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: $amount,

            data: [
                'source' =>
                    TransactionSource::System,

                'occurred_at' =>
                    CarbonImmutable::parse(
                        $occurredAt,
                        'Asia/Jakarta'
                    ),

                'description' =>
                    'Pengeluaran rekomendasi',

                'counterparty' => null,
                'reference_number' => null,
                'notes' => null,
            ]
        );
    }
}
