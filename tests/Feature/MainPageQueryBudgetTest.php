<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodStatus;
use App\Enums\BudgetPeriodType;
use App\Enums\FinanceFlowType;
use App\Enums\TransactionEntryRole;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainPageQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_index_query_count_does_not_grow_per_card(): void
    {
        $user = $this->user();

        $this->createBudgets(
            $user,
            3,
            0
        );

        $smallResponse = $this
            ->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk();

        $smallCount = $this->queryCount(
            $smallResponse
        );

        $this->createBudgets(
            $user,
            9,
            3
        );

        $largeResponse = $this
            ->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk();

        $largeCount = $this->queryCount(
            $largeResponse
        );

        $this->assertLessThanOrEqual(
            $smallCount + 2,
            $largeCount,
            sprintf(
                'Query halaman anggaran bertambah dari %d menjadi %d saat jumlah kartu meningkat.',
                $smallCount,
                $largeCount
            )
        );
    }

    public function test_transaction_index_query_count_does_not_grow_per_row(): void
    {
        $user = $this->user();

        [$account, $category] =
            $this->ledgerReferences($user);

        $this->createTransactions(
            $user,
            $account,
            $category,
            3
        );

        $smallResponse = $this
            ->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk();

        $smallCount = $this->queryCount(
            $smallResponse
        );

        $this->createTransactions(
            $user,
            $account,
            $category,
            18
        );

        $largeResponse = $this
            ->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk();

        $largeCount = $this->queryCount(
            $largeResponse
        );

        $this->assertLessThanOrEqual(
            $smallCount + 2,
            $largeCount,
            sprintf(
                'Query halaman transaksi bertambah dari %d menjadi %d saat jumlah baris meningkat.',
                $smallCount,
                $largeCount
            )
        );
    }

    public function test_dashboard_stays_inside_initial_query_budget(): void
    {
        $user = $this->user();

        $this->createBudgets(
            $user,
            6,
            0
        );

        [$account, $category] =
            $this->ledgerReferences($user);

        $this->createTransactions(
            $user,
            $account,
            $category,
            12
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $queryCount = $this->queryCount(
            $response
        );

        $this->assertLessThanOrEqual(
            45,
            $queryCount,
            'Dashboard melewati baseline awal 45 query.'
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

        /*
        * Ambil ulang seluruh kolom pengguna dari database,
        * termasuk kolom nullable seperti profile_photo_path.
        */
        return $user->refresh();
    }

    private function createBudgets(
        User $user,
        int $count,
        int $offset
    ): void {
        for ($index = 0; $index < $count; $index++) {
            $number = $offset + $index + 1;

            $category = FinanceCategory::query()
                ->create([
                    'user_id' => $user->id,
                    'name' => 'Kategori '.$number,
                    'flow_type' =>
                        FinanceFlowType::Expense,
                    'icon' => 'wallet-cards',
                    'color' => '#2563EB',
                    'is_system' => false,
                    'is_active' => true,
                    'sort_order' => $number,
                ]);

            $budget = Budget::query()->create([
                'user_id' => $user->id,
                'finance_category_id' =>
                    $category->id,
                'active_finance_category_id' =>
                    $category->id,
                'name' => 'Anggaran '.$number,
                'amount' => '1000000.00',
                'period_type' =>
                    BudgetPeriodType::Monthly,
                'warning_threshold_percent' =>
                    '80.00',
                'start_date' => now()
                    ->startOfMonth()
                    ->toDateString(),
                'end_date' => null,
                'is_recurring' => true,
                'is_active' => true,
            ]);

            BudgetPeriod::query()->create([
                'budget_id' => $budget->id,
                'period_start' => now()
                    ->startOfMonth()
                    ->toDateString(),
                'period_end' => now()
                    ->endOfMonth()
                    ->toDateString(),
                'budget_amount' => '1000000.00',
                'used_amount' => '250000.00',
                'remaining_amount' => '750000.00',
                'usage_percent' => '25.00',
                'status' =>
                    BudgetPeriodStatus::Active,
            ]);
        }
    }

    /**
     * @return array{0: Account, 1: FinanceCategory}
     */
    private function ledgerReferences(
        User $user
    ): array {
        $account = Account::factory()
            ->for($user)
            ->create();

        $category = FinanceCategory::query()
            ->create([
                'user_id' => $user->id,
                'name' => 'Pengeluaran Umum',
                'flow_type' =>
                    FinanceFlowType::Expense,
                'icon' => 'receipt-text',
                'color' => '#DC2626',
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 100,
            ]);

        return [
            $account,
            $category,
        ];
    }

    private function createTransactions(
        User $user,
        Account $account,
        FinanceCategory $category,
        int $count
    ): void {
        for ($index = 0; $index < $count; $index++) {
            $transaction = Transaction::query()
                ->create([
                    'user_id' => $user->id,
                    'type' =>
                        TransactionType::Expense,
                    'status' =>
                        TransactionStatus::Posted,
                    'source' =>
                        TransactionSource::Manual,
                    'occurred_at' => now()
                        ->subMinutes($index),
                    'description' =>
                        'Transaksi '.$index,
                    'posted_at' => now(),
                ]);

            TransactionEntry::query()->create([
                'transaction_id' =>
                    $transaction->id,
                'account_id' => $account->id,
                'finance_category_id' =>
                    $category->id,
                'amount' => '-10000.00',
                'role' =>
                    TransactionEntryRole::Principal,
                'memo' => null,
            ]);
        }
    }

    private function queryCount(
        \Illuminate\Testing\TestResponse $response
    ): int {
        $value = $response->headers->get(
            'X-DB-Query-Count'
        );

        $this->assertNotNull(
            $value,
            'Header X-DB-Query-Count tidak tersedia.'
        );

        return (int) $value;
    }
}
