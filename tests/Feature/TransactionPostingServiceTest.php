<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\TransactionEntryRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\TransactionPostingService;
use Database\Seeders\FinanceCategorySeeder;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_is_idempotent(): void
    {
        $user = $this->userWithPreference();

        $this->seed(FinanceCategorySeeder::class);
        $firstCount = $user->financeCategories()->count();

        $this->seed(FinanceCategorySeeder::class);
        $secondCount = $user->financeCategories()->count();

        $this->assertSame(15, $firstCount);
        $this->assertSame($firstCount, $secondCount);

        $this->assertDatabaseHas('finance_categories', [
            'user_id' => $user->id,
            'name' => 'Biaya Admin',
            'flow_type' => FinanceFlowType::Expense->value,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    public function test_income_is_posted_and_increases_balance(): void
    {
        $user = $this->userWithPreference();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $category = FinanceCategory::factory()
            ->income()
            ->create([
                'user_id' => $user->id,
                'name' => 'Gaji',
            ]);

        $transaction = app(
            TransactionPostingService::class
        )->postIncome(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '50000.50',
            data: [
                'description' => 'Gaji proyek',
                'counterparty' => 'Klien',
            ]
        );

        $account->refresh();

        $this->assertSame(
            TransactionType::Income,
            $transaction->type
        );

        $this->assertSame(
            TransactionStatus::Posted,
            $transaction->status
        );

        $this->assertSame(
            '150000.50',
            $account->cached_balance
        );

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'finance_category_id' => $category->id,
            'amount' => '50000.50',
            'role' => TransactionEntryRole::Principal->value,
        ]);
    }

    public function test_expense_is_posted_and_reduces_balance(): void
    {
        $user = $this->userWithPreference();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
                'name' => 'Makanan',
            ]);

        $transaction = app(
            TransactionPostingService::class
        )->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '25000'
        );

        $account->refresh();

        $this->assertSame(
            TransactionType::Expense,
            $transaction->type
        );

        $this->assertSame(
            '75000.00',
            $account->cached_balance
        );

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => '-25000.00',
        ]);
    }

    public function test_transfer_with_fee_creates_three_entries(): void
    {
        $user = $this->userWithPreference();

        $this->seed(FinanceCategorySeeder::class);

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
        ]);

        $destination = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'SeaBank',
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $transaction = app(
            TransactionPostingService::class
        )->postTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationAccountId: $destination->id,
            amount: '200000',
            adminFee: '2500',
            data: [
                'description' => 'Pindahkan dana tabungan',
            ]
        );

        $source->refresh();
        $destination->refresh();

        $this->assertSame(
            TransactionType::Transfer,
            $transaction->type
        );

        $this->assertSame(
            '297500.00',
            $source->cached_balance
        );

        $this->assertSame(
            '300000.00',
            $destination->cached_balance
        );

        $this->assertSame(
            3,
            $transaction->entries()->count()
        );

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $source->id,
            'amount' => '-200000.00',
            'role' => TransactionEntryRole::Principal->value,
        ]);

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $destination->id,
            'amount' => '200000.00',
            'role' => TransactionEntryRole::Principal->value,
        ]);

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $source->id,
            'amount' => '-2500.00',
            'role' => TransactionEntryRole::Fee->value,
        ]);
    }

    public function test_insufficient_balance_rolls_back_expense(): void
    {
        $user = $this->userWithPreference();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '10000.00',
            'cached_balance' => '10000.00',
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        try {
            app(TransactionPostingService::class)
                ->postExpense(
                    user: $user,
                    accountId: $account->id,
                    categoryId: $category->id,
                    amount: '20000'
                );

            $this->fail(
                'DomainException seharusnya dilempar.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'Saldo rekening '.$account->name
                    .' tidak mencukupi.',
                $exception->getMessage()
            );
        }

        $account->refresh();

        $this->assertSame(
            '10000.00',
            $account->cached_balance
        );

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
    }

    public function test_category_flow_must_match_transaction_type(): void
    {
        $user = $this->userWithPreference();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '100000.00',
        ]);

        $incomeCategory = FinanceCategory::factory()
            ->income()
            ->create([
                'user_id' => $user->id,
            ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Kategori tidak sesuai dengan jenis transaksi.'
        );

        app(TransactionPostingService::class)
            ->postExpense(
                user: $user,
                accountId: $account->id,
                categoryId: $incomeCategory->id,
                amount: '10000'
            );
    }

    public function test_user_cannot_post_transaction_to_another_users_account(): void
    {
        $user = $this->userWithPreference();
        $otherUser = $this->userWithPreference();

        $otherAccount = Account::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $category = FinanceCategory::factory()
            ->income()
            ->create([
                'user_id' => $user->id,
            ]);

        $this->expectException(
            ModelNotFoundException::class
        );

        app(TransactionPostingService::class)
            ->postIncome(
                user: $user,
                accountId: $otherAccount->id,
                categoryId: $category->id,
                amount: '10000'
            );
    }

    public function test_cancelling_expense_restores_balance(): void
    {
        $user = $this->userWithPreference();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $service = app(
            TransactionPostingService::class
        );

        $transaction = $service->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '25000'
        );

        $this->assertSame(
            '75000.00',
            $account->fresh()->cached_balance
        );

        $cancelledTransaction = $service->cancel(
            user: $user,
            transactionId: $transaction->id,
            reason: 'Salah memasukkan transaksi'
        );

        $this->assertSame(
            TransactionStatus::Cancelled,
            $cancelledTransaction->status
        );

        $this->assertNotNull(
            $cancelledTransaction->cancelled_at
        );

        $this->assertSame(
            '100000.00',
            $account->fresh()->cached_balance
        );

        $this->assertSame(
            'Salah memasukkan transaksi',
            $cancelledTransaction
                ->metadata['cancellation_reason']
        );

        $this->assertDatabaseCount(
            'transaction_entries',
            1
        );
    }

    public function test_cancelled_transaction_cannot_be_cancelled_again(): void
    {
        $user = $this->userWithPreference();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '100000.00',
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $service = app(
            TransactionPostingService::class
        );

        $transaction = $service->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '10000'
        );

        $service->cancel(
            user: $user,
            transactionId: $transaction->id
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Hanya transaksi tercatat yang dapat dibatalkan.'
        );

        $service->cancel(
            user: $user,
            transactionId: $transaction->id
        );
    }

    private function userWithPreference(): User
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
}
