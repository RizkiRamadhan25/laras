<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetManagementService;
use App\Services\BudgetService;
use App\Services\BudgetUsageSyncService;
use App\Services\PersonalRecommendationService;
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAlertDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_warning_notification_is_sent_once(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
            $budget,
        ] = $this->context();

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '800000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T12:00',
                ]
            );

        $notification = $user
            ->notifications()
            ->firstOrFail();

        $this->assertSame(
            'budget_warning',
            $notification->data['kind']
        );

        $this->assertSame(
            $budget->id,
            $notification->data[
                'budget_id'
            ]
        );

        app(BudgetUsageSyncService::class)
            ->syncBudgetForDate(
                $budget,
                CarbonImmutable::parse(
                    '2026-08-15'
                )
            );

        $this->assertSame(
            1,
            $user->notifications()->count()
        );
    }

    public function test_exceeded_notification_is_sent_after_warning_without_duplicate(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
            $budget,
        ] = $this->context();

        $postingService = app(
            TransactionPostingService::class
        );

        $postingService->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '800000.00',
            data: [
                'occurred_at' =>
                    '2026-08-15T12:00',
            ]
        );

        $postingService->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '250000.00',
            data: [
                'occurred_at' =>
                    '2026-08-15T13:00',
            ]
        );

        $kinds = $user->notifications()
            ->get()
            ->pluck('data')
            ->map(
                static fn (array $data): ?string =>
                    $data['kind'] ?? null
            )
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            [
                'budget_exceeded',
                'budget_warning',
            ],
            $kinds
        );

        app(BudgetUsageSyncService::class)
            ->syncBudgetForDate(
                $budget,
                CarbonImmutable::parse(
                    '2026-08-15'
                )
            );

        $this->assertSame(
            2,
            $user->notifications()->count()
        );
    }

    public function test_opening_budget_notification_redirects_to_selected_period(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
            $budget,
        ] = $this->context();

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '800000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T12:00',
                ]
            );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $notification = $user
            ->notifications()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'notifications.open',
                    $notification->id
                )
            )
            ->assertRedirect(
                route(
                    'budgets.show',
                    [
                        'budget' => $budget,
                        'period' => $period->id,
                    ]
                )
            );

        $this->assertNotNull(
            $notification
                ->fresh()
                ->read_at
        );
    }

    public function test_dashboard_receives_budget_overview_and_attention_item(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
            $budget,
        ] = $this->context();

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '850000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T12:00',
                ]
            );

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'budgetOverview',
                function (array $overview) use (
                    $budget
                ): bool {
                    $attention = $overview[
                        'attention_items'
                    ]->first();

                    return $overview['summary'][
                        'active'
                    ] === 1
                        && $overview['summary'][
                            'warning'
                        ] === 1
                        && is_array($attention)
                        && $attention['budget']->id
                            === $budget->id;
                }
            )
            ->assertSee(
                'Anggaran perlu perhatian'
            )
            ->assertSee($budget->name);
    }

    public function test_budget_alert_is_included_in_personal_recommendations(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
            $budget,
        ] = $this->context();

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '800000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T12:00',
                ]
            );

        $item = app(
            PersonalRecommendationService::class
        )->build($user)['items']
            ->firstWhere(
                'kind',
                'budget_warning'
            );

        $this->assertNotNull($item);

        $this->assertStringContainsString(
            $budget->name,
            $item['title']
        );

        $this->assertSame(
            'warning',
            $item['severity']
        );

        $this->assertStringContainsString(
            route(
                'budgets.show',
                $budget
            ),
            $item['action_url']
        );
    }

    public function test_lowering_budget_limit_can_generate_exceeded_alert(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
            $budget,
        ] = $this->context();

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '500000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T12:00',
                ]
            );

        $this->assertSame(
            0,
            $user->notifications()->count()
        );

        app(BudgetManagementService::class)
            ->update(
                user: $user,
                budget: $budget,
                attributes: [
                    'name' => $budget->name,
                    'amount' => '400000.00',

                    'warning_threshold_percent' =>
                        '80.00',
                ]
            );

        $notification = $user
            ->notifications()
            ->firstOrFail();

        $this->assertSame(
            'budget_exceeded',
            $notification->data['kind']
        );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Account,
     *     2: FinanceCategory,
     *     3: Budget
     * }
     */
    private function context(): array
    {
        $user = User::factory()->create([
            'onboarding_completed_at' =>
                now(),

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

            'initial_balance' =>
                '5000000.00',

            'cached_balance' =>
                '5000000.00',

            'is_active' => true,
        ]);

        $category = new FinanceCategory();

        $category->forceFill([
            'user_id' => $user->id,
            'flow_type' => 'expense',
            'name' => 'Makanan',
            'icon' => 'wallet-cards',
            'color' => '#2563EB',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 0,
        ])->save();

        $budget = app(BudgetService::class)
            ->create(
                $user,
                $category,
                [
                    'name' =>
                        'Anggaran Makanan',

                    'amount' =>
                        '1000000.00',

                    'period_type' =>
                        BudgetPeriodType::Monthly
                            ->value,

                    'warning_threshold_percent' =>
                        '80.00',

                    'start_date' =>
                        '2026-08-01',
                ]
            );

        return [
            $user,
            $account,
            $category,
            $budget,
        ];
    }
}
