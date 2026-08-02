<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Models\Account;
use App\Models\Activity;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_empty_recommendation_state(): void
    {
        [$user] = $this->context();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                'Rekomendasi personal'
            )
            ->assertSee(
                'Semuanya terkendali'
            )
            ->assertSee(
                route(
                    'recommendations.index'
                ),
                false
            );
    }

    public function test_dashboard_only_receives_three_top_recommendations(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [$user] = $this->context();

        for (
            $number = 1;
            $number <= 4;
            $number++
        ) {
            Activity::factory()
                ->urgent()
                ->create([
                    'user_id' => $user->id,

                    'title' => 'Aktivitas prioritas '
                        .$number,

                    'status' => ActivityStatus::Planned,

                    'due_at' => $reference->addHours(
                        $number
                    ),
                ]);
        }

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'dashboardRecommendations',
                function (
                    array $recommendations
                ): bool {
                    return $recommendations[
                        'items'
                    ]->count() === 3
                        && $recommendations[
                            'summary'
                        ]['total'] >= 4
                        && $recommendations[
                            'has_more'
                        ] === true;
                }
            )
            ->assertSee(
                'Menampilkan tiga dari'
            );
    }

    public function test_failed_billing_is_the_first_dashboard_recommendation(): void
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

                'title' => 'Aktivitas mendesak',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addHour(),
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
            'status' => SubscriptionBillingStatus::Failed,

            'attempted_at' => now(),

            'failure_reason' => 'Saldo rekening tidak mencukupi.',

            'metadata' => [
                'attempt_count' => 1,
            ],
        ])->save();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'dashboardRecommendations',
                function (
                    array $recommendations
                ): bool {
                    $first =
                        $recommendations[
                            'items'
                        ]->first();

                    return is_array($first)
                        && $first['kind']
                            === 'billing_failed'
                        && $first['score']
                            === 100;
                }
            )
            ->assertSee(
                'Tagihan Spotify gagal diproses'
            );
    }

    public function test_dashboard_recommendations_do_not_include_other_users_data(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [$user] = $this->context();

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
                    'name' => 'Langganan pengguna lain',
                ]
            );

        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $billing->forceFill([
            'status' => SubscriptionBillingStatus::Failed,

            'attempted_at' => now(),

            'failure_reason' => 'Tagihan pengguna lain gagal.',
        ])->save();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'dashboardRecommendations',
                function (
                    array $recommendations
                ): bool {
                    return $recommendations[
                        'items'
                    ]->doesntContain(
                        fn (
                            array $item
                        ): bool => str_contains(
                            $item['title'],
                            'Langganan pengguna lain'
                        )
                    );
                }
            )
            ->assertDontSee(
                'Langganan pengguna lain'
            );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Account,
     *     2: FinanceCategory
     * }
     */
    private function context(): array
    {
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

            'initial_balance' => '1000000.00',

            'cached_balance' => '1000000.00',

            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
                'name' => 'Hiburan',

                'flow_type' => FinanceFlowType::Expense,

                'is_active' => true,
            ]);

        return [
            $user,
            $account,
            $category,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
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
                    'account_id' => $account->id,

                    'finance_category_id' => $category->id,

                    'name' => 'Spotify',
                    'provider' => 'Spotify',
                    'amount' => '59000',

                    'interval_unit' => 'month',

                    'interval_count' => 1,

                    'started_on' => '2026-08-10',

                    'next_billing_on' => '2026-08-10',

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
}
