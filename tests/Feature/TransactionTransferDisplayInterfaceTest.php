<?php

namespace Tests\Feature;

use App\Enums\TransactionTransferKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferDisplayInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_history_distinguishes_internal_and_external_transfers(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'initial_balance' => '1000000.00',
            'cached_balance' => '1000000.00',
            'is_active' => true,
        ]);

        $destination = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'SeaBank Tabungan',
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::Internal->value,
                'account_id' => $source->id,
                'destination_account_id' => $destination->id,
                'amount' => '100000',
                'admin_fee' => '0',
                'occurred_at' => '2026-08-05T14:00',
                'description' => 'Pindah dana tabungan',
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::External->value,
                'account_id' => $source->id,
                'amount' => '150000',
                'admin_fee' => '0',
                'occurred_at' => '2026-08-05T15:00',
                'external_destination_name' => 'Penerima Eksternal',
                'external_destination_institution' => 'BCA',
                'external_destination_account_number' => '1234567890',
                'description' => 'Transfer keluar Laras',
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee(
                'data-transaction-transfer-kind="internal"',
                false
            )
            ->assertSee(
                'data-transaction-transfer-kind="external"',
                false
            )
            ->assertSee('Antar-rekening Laras')
            ->assertSee('Ke pihak luar Laras')
            ->assertSee('BCA Utama')
            ->assertSee('SeaBank Tabungan')
            ->assertSee(
                'data-transaction-external-destination',
                false
            )
            ->assertSee('Penerima Eksternal')
            ->assertSee('BCA');
    }

    public function test_external_transfer_detail_exposes_destination_information(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Mandiri Utama',
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::External->value,
                'account_id' => $source->id,
                'amount' => '200000',
                'admin_fee' => '0',
                'occurred_at' => '2026-08-05T16:00',
                'external_destination_name' => 'Rizki Ramadhan',
                'external_destination_institution' => 'Bank BCA',
                'external_destination_account_number' => '9876543210',
                'description' => 'Transfer biaya kegiatan',
            ])
            ->assertRedirect();

        $transaction = Transaction::query()
            ->where(
                'description',
                'Transfer biaya kegiatan'
            )
            ->firstOrFail();

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
                'data-transaction-transfer-kind="external"',
                false
            )
            ->assertSee(
                'data-transaction-transfer-details',
                false
            )
            ->assertSee(
                'data-transaction-external-destination',
                false
            )
            ->assertSee('Ke pihak luar Laras')
            ->assertSee('Mandiri Utama')
            ->assertSee('Rizki Ramadhan')
            ->assertSee('Bank BCA')
            ->assertSee('9876543210');
    }

    public function test_internal_transfer_detail_exposes_source_and_laras_destination(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Harian',
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
            'is_active' => true,
        ]);

        $destination = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'SeaBank Simpanan',
            'initial_balance' => '50000.00',
            'cached_balance' => '50000.00',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::Internal->value,
                'account_id' => $source->id,
                'destination_account_id' => $destination->id,
                'amount' => '100000',
                'admin_fee' => '0',
                'occurred_at' => '2026-08-05T17:00',
                'description' => 'Transfer tabungan internal',
            ])
            ->assertRedirect();

        $transaction = Transaction::query()
            ->where(
                'description',
                'Transfer tabungan internal'
            )
            ->firstOrFail();

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
                'data-transaction-transfer-kind="internal"',
                false
            )
            ->assertSee(
                'data-transaction-transfer-details',
                false
            )
            ->assertSee('Antar-rekening Laras')
            ->assertSee('BCA Harian')
            ->assertSee('SeaBank Simpanan')
            ->assertDontSee(
                'data-transaction-external-destination',
                false
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
