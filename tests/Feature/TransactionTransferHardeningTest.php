<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionTransferKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\TransactionDeletionService;
use App\Services\TransactionPostingService;
use Database\Seeders\FinanceCategorySeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_transfer_rejects_the_same_source_and_destination_account(): void
    {
        $user = $this->completedUser();

        $account = $this->account(
            $user,
            'BCA Utama',
            '500000.00'
        );

        $createRoute = route(
            'transactions.create',
            ['type' => TransactionType::Transfer->value]
        );

        $this
            ->actingAs($user)
            ->from($createRoute)
            ->post(
                route('transactions.store'),
                [
                    'type' => TransactionType::Transfer->value,
                    'transfer_kind' => TransactionTransferKind::Internal->value,
                    'account_id' => $account->id,
                    'destination_account_id' => $account->id,
                    'amount' => '100000',
                    'admin_fee' => '0',
                    'occurred_at' => '2026-08-05T18:00',
                ]
            )
            ->assertRedirect($createRoute)
            ->assertSessionHasErrors(
                'destination_account_id'
            );

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertSame(
            '500000.00',
            $account->fresh()->cached_balance
        );
    }

    public function test_internal_transfer_rejects_another_users_destination_account(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $source = $this->account(
            $user,
            'BCA Pribadi',
            '500000.00'
        );

        $otherDestination = $this->account(
            $otherUser,
            'Rekening Pengguna Lain',
            '100000.00'
        );

        $createRoute = route(
            'transactions.create',
            ['type' => TransactionType::Transfer->value]
        );

        $this
            ->actingAs($user)
            ->from($createRoute)
            ->post(
                route('transactions.store'),
                [
                    'type' => TransactionType::Transfer->value,
                    'transfer_kind' => TransactionTransferKind::Internal->value,
                    'account_id' => $source->id,
                    'destination_account_id' => $otherDestination->id,
                    'amount' => '100000',
                    'admin_fee' => '0',
                    'occurred_at' => '2026-08-05T18:15',
                ]
            )
            ->assertRedirect($createRoute)
            ->assertSessionHasErrors(
                'destination_account_id'
            );

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertSame(
            '500000.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            '100000.00',
            $otherDestination->fresh()->cached_balance
        );
    }

    public function test_external_transfer_ignores_internal_destination_input(): void
    {
        $user = $this->completedUser();

        $source = $this->account(
            $user,
            'Mandiri Utama',
            '500000.00'
        );

        $larasDestination = $this->account(
            $user,
            'SeaBank Simpanan',
            '100000.00'
        );

        $this
            ->actingAs($user)
            ->post(
                route('transactions.store'),
                [
                    'type' => TransactionType::Transfer->value,
                    'transfer_kind' => TransactionTransferKind::External->value,
                    'account_id' => $source->id,
                    'destination_account_id' => $larasDestination->id,
                    'amount' => '150000',
                    'admin_fee' => '0',
                    'occurred_at' => '2026-08-05T18:30',
                    'external_destination_name' => 'Penerima Eksternal',
                    'external_destination_institution' => 'Bank BCA',
                    'external_destination_account_number' => '1234567890',
                    'description' => 'Transfer eksternal terisolasi',
                ]
            )
            ->assertRedirect();

        $transaction = Transaction::query()
            ->where(
                'description',
                'Transfer eksternal terisolasi'
            )
            ->firstOrFail();

        $this->assertSame(
            TransactionTransferKind::External,
            $transaction->transferKind()
        );

        $this->assertSame(
            [
                'name' => 'Penerima Eksternal',
                'institution' => 'Bank BCA',
                'account_number' => '1234567890',
            ],
            $transaction->externalDestination()
        );

        $this->assertSame(
            1,
            $transaction->entries()->count()
        );

        $this->assertSame(
            [$source->id],
            $transaction->entries()
                ->pluck('account_id')
                ->map(
                    static fn (mixed $id): int => (int) $id
                )
                ->unique()
                ->values()
                ->all()
        );

        $this->assertSame(
            '350000.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            '100000.00',
            $larasDestination->fresh()->cached_balance
        );
    }

    public function test_failed_external_transfer_leaves_ledger_and_balance_unchanged(): void
    {
        $user = $this->completedUser();

        $source = $this->account(
            $user,
            'BCA Terbatas',
            '100000.00'
        );

        $transactionCount = Transaction::query()->count();
        $entryCount = $this->transactionEntryCount();

        try {
            app(TransactionPostingService::class)
                ->postExternalTransfer(
                    user: $user,
                    sourceAccountId: $source->id,
                    destinationName: 'Tujuan Eksternal',
                    destinationInstitution: 'BCA',
                    destinationAccountNumber: '1234567890',
                    amount: '150000',
                    adminFee: '0'
                );

            $this->fail(
                'Transfer seharusnya gagal karena saldo tidak mencukupi.'
            );
        } catch (DomainException) {
            // Kondisi gagal memang diharapkan.
        }

        $this->assertSame(
            $transactionCount,
            Transaction::query()->count()
        );

        $this->assertSame(
            $entryCount,
            $this->transactionEntryCount()
        );

        $this->assertSame(
            '100000.00',
            $source->fresh()->cached_balance
        );
    }

    public function test_second_cancellation_does_not_change_balance_or_ledger_again(): void
    {
        $user = $this->completedUser();

        $source = $this->account(
            $user,
            'BCA Pembatalan',
            '500000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postExternalTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationName: 'Penerima Eksternal',
            destinationInstitution: null,
            destinationAccountNumber: null,
            amount: '100000',
            adminFee: '0'
        );

        app(TransactionPostingService::class)
            ->cancel(
                user: $user,
                transactionId: $transaction->id,
                reason: 'Pembatalan pertama'
            );

        $balanceAfterFirstCancellation =
            $source->fresh()->cached_balance;

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
                    'reason' => 'Pembatalan kedua',
                ]
            )
            ->assertRedirectToRoute(
                'transactions.show',
                $transaction->id
            )
            ->assertSessionHas('warning');

        $this->assertSame(
            TransactionStatus::Cancelled,
            $transaction->fresh()->status
        );

        $this->assertSame(
            $balanceAfterFirstCancellation,
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            $entryIds,
            $transaction->entries()
                ->orderBy('id')
                ->pluck('id')
                ->all()
        );
    }

    public function test_legacy_transfer_without_kind_metadata_falls_back_to_internal(): void
    {
        $user = $this->completedUser();

        $transaction = Transaction::factory()
            ->create([
                'user_id' => $user->id,
                'type' => TransactionType::Transfer,
                'metadata' => null,
            ]);

        $this->assertSame(
            TransactionTransferKind::Internal,
            $transaction->transferKind()
        );

        $this->assertTrue(
            $transaction->isInternalTransfer()
        );

        $this->assertFalse(
            $transaction->isExternalTransfer()
        );

        $this->assertNull(
            $transaction->externalDestination()
        );
    }

    public function test_cancelled_external_transfer_with_fee_is_deleted_as_one_group(): void
    {
        $user = $this->completedUser();

        $this->seed(
            FinanceCategorySeeder::class
        );

        $source = $this->account(
            $user,
            'BCA Penghapusan',
            '500000.00'
        );

        $transaction = app(
            TransactionPostingService::class
        )->postExternalTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationName: 'Penerima Eksternal',
            destinationInstitution: 'Bank BCA',
            destinationAccountNumber: '9988776655',
            amount: '200000',
            adminFee: '2500'
        );

        app(TransactionPostingService::class)
            ->cancel(
                user: $user,
                transactionId: $transaction->id,
                reason: 'Dibatalkan sebelum dihapus'
            );

        $entryIds = $transaction->entries()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertCount(
            2,
            $entryIds
        );

        $source->forceFill([
            'cached_balance' => '1.00',
        ])->save();

        app(TransactionDeletionService::class)
            ->deletePermanently(
                user: $user,
                transactionId: $transaction->id
            );

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
    }

    private function transactionEntryCount(): int
    {
        return (int) \DB::table(
            'transaction_entries'
        )->count();
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
