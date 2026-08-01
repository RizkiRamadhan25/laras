<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use Database\Seeders\FinanceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_user_can_view_transaction_pages(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $this
            ->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Transaksi');

        $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.create',
                    ['type' => 'expense']
                )
            )
            ->assertOk()
            ->assertSee('Catat transaksi')
            ->assertSee('Pengeluaran');
    }

    public function test_user_can_post_income_from_form(): void
    {
        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $category = $user->financeCategories()
            ->where('name', 'Gaji')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'income',
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => '50000.50',
                'occurred_at' => '2026-08-01T10:30',
                'description' => 'Pendapatan proyek',
                'counterparty' => 'Klien',
                'reference_number' => null,
                'notes' => null,
            ]);

        $transaction = Transaction::query()->firstOrFail();

        $response
            ->assertRedirectToRoute(
                'transactions.show',
                $transaction->id
            )
            ->assertSessionHas('status');

        $this->assertSame(
            '150000.50',
            $account->fresh()->cached_balance
        );

        $this->assertSame(
            TransactionStatus::Posted,
            $transaction->status
        );
    }

    public function test_user_can_post_expense_from_form(): void
    {
        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $category = $user->financeCategories()
            ->where('name', 'Makanan & Minuman')
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => '25000',
                'occurred_at' => '2026-08-01T12:00',
                'description' => 'Makan siang',
            ])
            ->assertRedirect();

        $this->assertSame(
            '75000.00',
            $account->fresh()->cached_balance
        );

        $this->assertDatabaseHas(
            'transaction_entries',
            [
                'account_id' => $account->id,
                'amount' => '-25000.00',
            ]
        );
    }

    public function test_user_can_post_transfer_with_fee_from_form(): void
    {
        $user = $this->completedUser();

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

        $this->seed(FinanceCategorySeeder::class);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'account_id' => $source->id,
                'destination_account_id' => $destination->id,
                'amount' => '200000',
                'admin_fee' => '2500',
                'occurred_at' => '2026-08-01T13:00',
                'description' => 'Transfer tabungan',
            ])
            ->assertRedirect();

        $this->assertSame(
            '297500.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            '300000.00',
            $destination->fresh()->cached_balance
        );

        $this->assertDatabaseCount(
            'transaction_entries',
            3
        );
    }

    public function test_user_cannot_use_another_users_account(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $otherAccount = Account::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $category = FinanceCategory::query()
            ->where('user_id', $user->id)
            ->where('name', 'Gaji')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'type' => 'income',
                'account_id' => $otherAccount->id,
                'category_id' => $category->id,
                'amount' => '10000',
                'occurred_at' => '2026-08-01T10:00',
            ]);

        $response
            ->assertRedirectToRoute('transactions.create')
            ->assertSessionHasErrors('account_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_cannot_view_another_users_transaction(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $transaction = Transaction::factory()
            ->posted()
            ->create([
                'user_id' => $otherUser->id,
            ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.show',
                    $transaction->id
                )
            )
            ->assertNotFound();
    }

    public function test_transaction_history_can_be_filtered_by_type(): void
    {
        $user = $this->completedUser();

        Transaction::factory()
            ->posted()
            ->income()
            ->create([
                'user_id' => $user->id,
                'description' => 'Pendapatan khusus',
            ]);

        Transaction::factory()
            ->posted()
            ->expense()
            ->create([
                'user_id' => $user->id,
                'description' => 'Pengeluaran khusus',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.index',
                    ['type' => 'income']
                )
            );

        $response
            ->assertOk()
            ->assertSee('Pendapatan khusus')
            ->assertDontSee('Pengeluaran khusus');
    }

    public function test_user_can_cancel_transaction_from_detail_page(): void
    {
        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $category = $user->financeCategories()
            ->where('name', 'Makanan & Minuman')
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => '25000',
                'occurred_at' => '2026-08-01T12:00',
                'description' => 'Makan siang',
            ]);

        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame(
            '75000.00',
            $account->fresh()->cached_balance
        );

        $response = $this
            ->actingAs($user)
            ->patch(
                route(
                    'transactions.cancel',
                    $transaction->id
                ),
                [
                    'reason' => 'Salah memasukkan transaksi',
                ]
            );

        $response
            ->assertRedirectToRoute(
                'transactions.show',
                $transaction->id
            )
            ->assertSessionHas('status');

        $this->assertSame(
            '100000.00',
            $account->fresh()->cached_balance
        );

        $this->assertSame(
            TransactionStatus::Cancelled,
            $transaction->fresh()->status
        );
    }

    private function completedUser(): User
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