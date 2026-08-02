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
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetUsageSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_posted_expense_updates_budget_usage(): void
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
            'Makanan'
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
                amount: '250000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T12:00',
                    'description' =>
                        'Makan dan belanja bulanan',
                ]
            );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '250000.00',
            $period->used_amount
        );

        $this->assertSame(
            '750000.00',
            $period->remaining_amount
        );

        $this->assertSame(
            '25.00',
            $period->usage_percent
        );
    }

    public function test_expense_from_other_category_does_not_change_budget(): void
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

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $otherCategory->id,
                amount: '100000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T13:00',
                ]
            );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '0.00',
            $period->used_amount
        );
    }

    public function test_cancelling_expense_recalculates_budget_usage(): void
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

        $transaction = app(
            TransactionPostingService::class
        )->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '400000.00',
            data: [
                'occurred_at' =>
                    '2026-08-15T14:00',
            ]
        );

        $this->assertSame(
            '400000.00',
            $budget
                ->periods()
                ->firstOrFail()
                ->used_amount
        );

        app(TransactionPostingService::class)
            ->cancel(
                user: $user,
                transactionId: $transaction->id,
                reason: 'Salah input'
            );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '0.00',
            $period->used_amount
        );

        $this->assertSame(
            '1000000.00',
            $period->remaining_amount
        );
    }

    public function test_existing_posted_expense_is_counted_when_budget_is_created(): void
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
            'Pendidikan'
        );

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '300000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-10T09:00',
                ]
            );

        $budget = $this->budget(
            $user,
            $category
        );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '300000.00',
            $period->used_amount
        );
    }

    public function test_expense_in_new_month_creates_and_updates_new_period(): void
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
                amount: '150000.00',
                data: [
                    'occurred_at' =>
                        '2026-09-10T08:00',
                ]
            );

        $this->assertSame(
            2,
            $budget
                ->periods()
                ->count()
        );

        $septemberPeriod = $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-09-01'
            )
            ->firstOrFail();

        $this->assertSame(
            '150000.00',
            $septemberPeriod->used_amount
        );
    }

    public function test_transfer_admin_fee_updates_admin_fee_budget(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();

        $source = $this->account(
            $user,
            'BCA',
            '5000000.00'
        );

        $destination = $this->account(
            $user,
            'SeaBank',
            '1000000.00'
        );

        $feeCategory = $this->category(
            $user,
            'expense',
            'Biaya Admin'
        );

        $budget = $this->budget(
            $user,
            $feeCategory
        );

        app(TransactionPostingService::class)
            ->postTransfer(
                user: $user,
                sourceAccountId: $source->id,
                destinationAccountId:
                    $destination->id,
                amount: '500000.00',
                adminFee: '2500.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T15:00',
                ]
            );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '2500.00',
            $period->used_amount
        );
    }

    public function test_local_timezone_boundary_is_mapped_to_correct_budget_month(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user(
            'Asia/Jakarta'
        );
        $account = $this->account($user);
        $category = $this->category(
            $user,
            'expense',
            'Kebutuhan Harian'
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
                amount: '50000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-01T00:30',
                ]
            );

        $augustPeriod = $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-08-01'
            )
            ->firstOrFail();

        $this->assertSame(
            '50000.00',
            $augustPeriod->used_amount
        );
    }

    public function test_inactive_budget_catches_up_when_reactivated(): void
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
            'Teknologi'
        );
        $budget = $this->budget(
            $user,
            $category
        );

        $managementService = app(
            BudgetManagementService::class
        );

        $managementService->deactivate(
            $user,
            $budget
        );

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '200000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T16:00',
                ]
            );

        $this->assertSame(
            '0.00',
            $budget
                ->periods()
                ->firstOrFail()
                ->used_amount
        );

        $managementService->activate(
            $user,
            $budget
        );

        $this->assertSame(
            '200000.00',
            $budget
                ->periods()
                ->firstOrFail()
                ->used_amount
        );
    }

    public function test_both_flow_category_can_be_used_for_expense_budget(): void
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
            'both',
            'Lainnya'
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
                amount: '125000.00',
                data: [
                    'occurred_at' =>
                        '2026-08-15T17:00',
                ]
            );

        $this->assertSame(
            '125000.00',
            $budget
                ->periods()
                ->firstOrFail()
                ->used_amount
        );
    }

    private function user(
        string $timezone = 'Asia/Jakarta'
    ): User {
        $user = User::factory()->create([
            'onboarding_completed_at' =>
                now(),
            'is_active' => true,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => $timezone,
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        return $user;
    }

    private function account(
        User $user,
        string $name = 'Rekening Utama',
        string $balance = '5000000.00'
    ): Account {
        return Account::factory()->create([
            'user_id' => $user->id,
            'name' => $name,
            'initial_balance' => $balance,
            'cached_balance' => $balance,
            'is_active' => true,
        ]);
    }

    private function category(
        User $user,
        string $flowType,
        string $name
    ): FinanceCategory {
        $category = new FinanceCategory();

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
                    'name' =>
                        'Anggaran '.$category->name,
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
    }
}
