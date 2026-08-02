<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetService;
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetUsagePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_flow_category_can_be_selected_from_budget_form(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();

        $category = $this->category(
            $user,
            'both',
            'Lainnya Fleksibel'
        );

        $this
            ->actingAs($user)
            ->get(
                route('budgets.create')
            )
            ->assertOk()
            ->assertSee(
                'Lainnya Fleksibel'
            );

        $response = $this
            ->actingAs($user)
            ->post(
                route('budgets.store'),
                [
                    'finance_category_id' => $category->id,

                    'name' => 'Anggaran Fleksibel',

                    'amount' => '750000.00',

                    'period_type' => BudgetPeriodType::Monthly
                        ->value,

                    'warning_threshold_percent' => '80.00',

                    'start_date' => '2026-08-01',
                ]
            );

        $budget = Budget::query()
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'budgets.show',
                $budget
            )
        );

        $this->assertSame(
            $category->id,
            $budget->finance_category_id
        );
    }

    public function test_budget_detail_displays_only_posted_entries_for_selected_category(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();
        $account = $this->account($user);

        $budgetCategory = $this->category(
            $user,
            'expense',
            'Makanan'
        );

        $otherCategory = $this->category(
            $user,
            'expense',
            'Transportasi'
        );

        $budget = $this->budget(
            $user,
            $budgetCategory
        );

        $postingService = app(
            TransactionPostingService::class
        );

        $postingService->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $budgetCategory->id,
            amount: '100000.00',
            data: [
                'occurred_at' => '2026-08-10T12:00',
                'description' => 'Makan valid anggaran',
            ]
        );

        $postingService->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $otherCategory->id,
            amount: '50000.00',
            data: [
                'occurred_at' => '2026-08-11T12:00',
                'description' => 'Transportasi kategori lain',
            ]
        );

        $cancelledTransaction =
            $postingService->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $budgetCategory->id,
                amount: '25000.00',
                data: [
                    'occurred_at' => '2026-08-12T12:00',
                    'description' => 'Pengeluaran dibatalkan',
                ]
            );

        $postingService->cancel(
            user: $user,
            transactionId: $cancelledTransaction->id,
            reason: 'Salah input'
        );

        $period = $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-08-01'
            )
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.show',
                    [
                        'budget' => $budget,
                        'period' => $period->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'Transaksi penyusun penggunaan'
            )
            ->assertSee(
                'Makan valid anggaran'
            )
            ->assertDontSee(
                'Transportasi kategori lain'
            )
            ->assertDontSee(
                'Pengeluaran dibatalkan'
            );
    }

    public function test_selected_period_only_displays_entries_from_that_period(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();
        $account = $this->account($user);
        $category = $this->category(
            $user,
            'expense',
            'Belanja'
        );
        $budget = $this->budget(
            $user,
            $category
        );

        $postingService = app(
            TransactionPostingService::class
        );

        $postingService->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '100000.00',
            data: [
                'occurred_at' => '2026-08-20T10:00',
                'description' => 'Belanja khusus Agustus',
            ]
        );

        $postingService->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '200000.00',
            data: [
                'occurred_at' => '2026-09-05T10:00',
                'description' => 'Belanja khusus September',
            ]
        );

        $augustPeriod = $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-08-01'
            )
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.show',
                    [
                        'budget' => $budget,
                        'period' => $augustPeriod->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'Belanja khusus Agustus'
            )
            ->assertDontSee(
                'Belanja khusus September'
            );
    }

    public function test_sync_command_recreates_missing_historical_period(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();
        $account = $this->account($user);
        $category = $this->category(
            $user,
            'expense',
            'Internet'
        );
        $budget = $this->budget(
            $user,
            $category
        );

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '175000.00',
                data: [
                    'occurred_at' => '2026-09-08T08:00',
                    'description' => 'Internet September',
                ]
            );

        $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-09-01'
            )
            ->delete();

        $this->assertDatabaseCount(
            'budget_periods',
            1
        );

        $this
            ->artisan(
                'budgets:sync-usage',
                [
                    '--budget' => $budget->id,
                ]
            )
            ->assertSuccessful();

        $septemberPeriod = $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-09-01'
            )
            ->firstOrFail();

        $this->assertSame(
            '2026-09-30',
            $septemberPeriod
                ->period_end
                ->toDateString()
        );

        $this->assertSame(
            '175000.00',
            $septemberPeriod->used_amount
        );
    }

    private function user(): User
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

        return $user;
    }

    private function account(
        User $user
    ): Account {
        return Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Rekening Utama',
            'initial_balance' => '5000000.00',
            'cached_balance' => '5000000.00',
            'is_active' => true,
        ]);
    }

    private function category(
        User $user,
        string $flowType,
        string $name
    ): FinanceCategory {
        $category = new FinanceCategory;

        $category->forceFill([
            'user_id' => $user->id,
            'flow_type' => $flowType,
            'name' => $name,
            'icon' => 'wallet-cards',
            'color' => '#2563EB',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $category->save();

        return $category;
    }

    private function budget(
        User $user,
        FinanceCategory $category
    ): Budget {
        return app(BudgetService::class)
            ->create(
                $user,
                $category,
                [
                    'name' => 'Anggaran '.$category->name,
                    'amount' => '1000000.00',
                    'period_type' => BudgetPeriodType::Monthly
                        ->value,
                    'warning_threshold_percent' => '80.00',
                    'start_date' => '2026-08-01',
                ]
            );
    }
}
