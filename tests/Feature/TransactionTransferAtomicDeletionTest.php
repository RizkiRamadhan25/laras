<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\TransactionPostingService;
use Database\Seeders\FinanceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferAtomicDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_transfer_is_cancelled_as_one_ledger_group(): void
    {
        $user = $this->completedUser();
        $this->seed(FinanceCategorySeeder::class);

        $source = $this->account(
            $user,
            'BCA Utama',
            '500000.00'
        );

        $destination = $this->account(
            $user,
            'SeaBank',
            '100000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationAccountId: $destination->id,
            amount: '200000',
            adminFee: '2500'
        );

        $entryIds = $transaction->entries()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'transactions.cancel',
                    $transaction->id
                ),
                [
                    'reason' => 'Salah tujuan',
                ]
            )
            ->assertRedirectToRoute(
                'transactions.show',
                $transaction->id
            );

        $this->assertSame(
            '500000.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            '100000.00',
            $destination->fresh()->cached_balance
        );

        $this->assertSame(
            TransactionStatus::Cancelled,
            $transaction->fresh()->status
        );

        $this->assertSame(
            $entryIds,
            $transaction->entries()
                ->orderBy('id')
                ->pluck('id')
                ->all()
        );
    }

    public function test_external_transfer_cancellation_reverses_principal_and_fee_together(): void
    {
        $user = $this->completedUser();
        $this->seed(FinanceCategorySeeder::class);

        $source = $this->account(
            $user,
            'Mandiri Utama',
            '500000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postExternalTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationName: 'Penerima Eksternal',
            destinationInstitution: 'BCA',
            destinationAccountNumber: '1234567890',
            amount: '200000',
            adminFee: '2500'
        );

        $this->assertSame(
            '297500.00',
            $source->fresh()->cached_balance
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'transactions.cancel',
                    $transaction->id
                ),
                [
                    'reason' => 'Transfer dibatalkan',
                ]
            )
            ->assertRedirect();

        $this->assertSame(
            '500000.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            2,
            $transaction->entries()->count()
        );
    }

    public function test_posted_transfer_must_be_cancelled_before_permanent_deletion(): void
    {
        $user = $this->completedUser();

        $source = $this->account(
            $user,
            'BCA',
            '500000.00'
        );

        $destination = $this->account(
            $user,
            'SeaBank',
            '100000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationAccountId: $destination->id,
            amount: '100000'
        );

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'transactions.destroy',
                    $transaction->id
                ),
                $this->validDeletionPayload()
            )
            ->assertRedirectToRoute(
                'transactions.show',
                $transaction->id
            )
            ->assertSessionHas(
                'warning',
                'Batalkan transaksi terlebih dahulu sebelum menghapusnya permanen.'
            );

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $transaction->id,
                'status' => TransactionStatus::Posted->value,
            ]
        );
    }

    public function test_cancelled_internal_transfer_is_deleted_with_all_entries_and_balances_rebuilt(): void
    {
        $user = $this->completedUser();

        $source = $this->account(
            $user,
            'BCA Harian',
            '500000.00'
        );

        $destination = $this->account(
            $user,
            'SeaBank Simpanan',
            '100000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationAccountId: $destination->id,
            amount: '125000'
        );

        app(TransactionPostingService::class)
            ->cancel(
                user: $user,
                transactionId: $transaction->id,
                reason: 'Salah rekening'
            );

        $entryIds = $transaction->entries()
            ->pluck('id')
            ->all();

        $source->forceFill([
            'cached_balance' => '1.00',
        ])->save();

        $destination->forceFill([
            'cached_balance' => '2.00',
        ])->save();

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'transactions.destroy',
                    $transaction->id
                ),
                $this->validDeletionPayload()
            )
            ->assertRedirectToRoute(
                'transactions.index'
            )
            ->assertSessionHas('status');

        $this->assertDatabaseMissing(
            'transactions',
            [
                'id' => $transaction->id,
            ]
        );

        foreach ($entryIds as $entryId) {
            $this->assertDatabaseMissing(
                'transaction_entries',
                [
                    'id' => $entryId,
                ]
            );
        }

        $this->assertSame(
            '500000.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            '100000.00',
            $destination->fresh()->cached_balance
        );
    }

    public function test_permanent_deletion_requires_password_confirmation_and_acknowledgement(): void
    {
        $user = $this->completedUser();

        $transaction = Transaction::factory()
            ->create([
                'user_id' => $user->id,
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

        $this
            ->actingAs($user)
            ->from(
                route(
                    'transactions.show',
                    $transaction->id
                )
            )
            ->delete(
                route(
                    'transactions.destroy',
                    $transaction->id
                ),
                [
                    'delete_current_password' => 'salah',
                    'confirmation' => 'HAPUS',
                ]
            )
            ->assertRedirectToRoute(
                'transactions.show',
                $transaction->id
            )
            ->assertSessionHasErrors([
                'delete_current_password',
                'confirmation',
                'acknowledge_permanent_deletion',
            ]);

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $transaction->id,
            ]
        );
    }

    public function test_user_cannot_delete_another_users_cancelled_transfer(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $transaction = Transaction::factory()
            ->create([
                'user_id' => $otherUser->id,
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'transactions.destroy',
                    $transaction->id
                ),
                $this->validDeletionPayload()
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $transaction->id,
                'user_id' => $otherUser->id,
            ]
        );
    }

    public function test_cancelled_transaction_page_exposes_permanent_delete_preview(): void
    {
        $user = $this->completedUser();

        $source = $this->account(
            $user,
            'BCA',
            '500000.00'
        );

        $destination = $this->account(
            $user,
            'SeaBank',
            '100000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationAccountId: $destination->id,
            amount: '100000'
        );

        app(TransactionPostingService::class)
            ->cancel(
                user: $user,
                transactionId: $transaction->id
            );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.show',
                    $transaction->id
                )
            )
            ->assertOk()
            ->assertSee(
                'data-transaction-permanent-delete',
                false
            )
            ->assertSee(
                'name="delete_current_password"',
                false
            )
            ->assertSee(
                'name="confirmation"',
                false
            )
            ->assertSee(
                'name="acknowledge_permanent_deletion"',
                false
            )
            ->assertSee('2 ledger entry')
            ->assertSee('2 rekening');
    }

    /**
     * @return array<string, string|int>
     */
    private function validDeletionPayload(): array
    {
        return [
            'delete_current_password' => 'password',
            'confirmation' => 'HAPUS TRANSAKSI',
            'acknowledge_permanent_deletion' => 1,
        ];
    }

    private function account(
        User $user,
        string $name,
        string $balance
    ): Account {
        return Account::factory()->create([
            'user_id' => $user->id,
            'name' => $name,
            'initial_balance' => $balance,
            'cached_balance' => $balance,
            'is_active' => true,
        ]);
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
