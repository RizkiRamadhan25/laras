<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\TransactionEntryRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionLedgerFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_models_have_expected_relationships_and_casts(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
        ]);

        $category = FinanceCategory::factory()->create([
            'user_id' => $user->id,
            'flow_type' => FinanceFlowType::Expense,
        ]);

        $transaction = Transaction::factory()
            ->posted()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $entry = TransactionEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'finance_category_id' => $category->id,
            'amount' => '-25000.50',
            'role' => TransactionEntryRole::Principal,
        ]);

        $this->assertTrue(
            $transaction->user->is($user)
        );

        $this->assertTrue(
            $entry->transaction->is($transaction)
        );

        $this->assertTrue(
            $entry->account->is($account)
        );

        $this->assertTrue(
            $entry->financeCategory->is($category)
        );

        $this->assertSame(
            TransactionType::Expense,
            $transaction->type
        );

        $this->assertSame(
            TransactionStatus::Posted,
            $transaction->status
        );

        $this->assertSame(
            TransactionEntryRole::Principal,
            $entry->role
        );

        $this->assertSame(
            '-25000.50',
            $entry->amount
        );
    }

    public function test_posted_entry_changes_calculated_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $transaction = Transaction::factory()
            ->posted()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        TransactionEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => '-25000.00',
        ]);

        $calculatedBalance = app(
            AccountBalanceService::class
        )->calculate($account);

        $this->assertSame(
            '75000.00',
            $calculatedBalance
        );
    }

    public function test_non_posted_transactions_do_not_change_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        foreach ([
            TransactionStatus::Draft,
            TransactionStatus::Pending,
            TransactionStatus::Failed,
            TransactionStatus::Cancelled,
        ] as $status) {
            $transaction = Transaction::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
            ]);

            TransactionEntry::factory()->create([
                'transaction_id' => $transaction->id,
                'account_id' => $account->id,
                'amount' => '-10000.00',
            ]);
        }

        $calculatedBalance = app(
            AccountBalanceService::class
        )->calculate($account);

        $this->assertSame(
            '100000.00',
            $calculatedBalance
        );
    }

    public function test_transfer_entries_affect_source_and_destination(): void
    {
        $user = User::factory()->create();

        $sourceAccount = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
        ]);

        $destinationAccount = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $transaction = Transaction::factory()
            ->posted()
            ->transfer()
            ->create([
                'user_id' => $user->id,
            ]);

        TransactionEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $sourceAccount->id,
            'amount' => '-200000.00',
        ]);

        TransactionEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $destinationAccount->id,
            'amount' => '200000.00',
        ]);

        $service = app(AccountBalanceService::class);

        $this->assertSame(
            '300000.00',
            $service->calculate($sourceAccount)
        );

        $this->assertSame(
            '300000.00',
            $service->calculate($destinationAccount)
        );
    }

    public function test_reconcile_updates_cached_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '0.00',
        ]);

        $transaction = Transaction::factory()
            ->posted()
            ->income()
            ->create([
                'user_id' => $user->id,
            ]);

        TransactionEntry::factory()->inflow()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => '50000.00',
        ]);

        $reconciledAccount = app(
            AccountBalanceService::class
        )->reconcile($account);

        $this->assertSame(
            '150000.00',
            $reconciledAccount->cached_balance
        );
    }

    public function test_soft_deleted_transaction_is_ignored_by_balance_calculation(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $transaction = Transaction::factory()
            ->posted()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        TransactionEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => '-30000.00',
        ]);

        $transaction->delete();

        $calculatedBalance = app(
            AccountBalanceService::class
        )->calculate($account);

        $this->assertSame(
            '100000.00',
            $calculatedBalance
        );

        $this->assertDatabaseHas(
            'transaction_entries',
            [
                'transaction_id' => $transaction->id,
                'amount' => '-30000.00',
            ]
        );
    }

    public function test_user_can_have_categories_and_transactions(): void
    {
        $user = User::factory()->create();

        FinanceCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(
            1,
            $user->financeCategories
        );

        $this->assertCount(
            1,
            $user->transactions
        );
    }
}
