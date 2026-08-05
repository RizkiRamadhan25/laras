<?php

namespace Tests\Feature;

use App\Enums\TransactionEntryRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTransferKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use Database\Seeders\FinanceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_transfer_form_defaults_to_internal_transfer(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '500000.00',
            'initial_balance' => '500000.00',
        ]);

        $destination = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '100000.00',
            'initial_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'account_id' => $source->id,
                'destination_account_id' => $destination->id,
                'amount' => '200000',
                'admin_fee' => '0',
                'occurred_at' => '2026-08-05T10:00',
            ])
            ->assertRedirect();

        $transaction = Transaction::query()
            ->with('entries')
            ->firstOrFail();

        $this->assertSame(
            TransactionTransferKind::Internal,
            $transaction->transferKind()
        );

        $this->assertTrue(
            $transaction->isInternalTransfer()
        );

        $this->assertSame(
            '200000.00',
            $transaction->displayAmount()
        );
    }

    public function test_user_can_post_external_transfer_without_crediting_another_laras_account(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '500000.00',
            'initial_balance' => '500000.00',
        ]);

        $unrelatedAccount = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '100000.00',
            'initial_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::External->value,
                'account_id' => $source->id,
                'amount' => '200000',
                'admin_fee' => '2500',
                'occurred_at' => '2026-08-05T11:00',
                'external_destination_name' => 'Rizki Ramadhan',
                'external_destination_institution' => 'BCA',
                'external_destination_account_number' => '1234567890',
                'description' => 'Transfer ke pihak luar',
            ])
            ->assertRedirect();

        $transaction = Transaction::query()
            ->with('entries')
            ->firstOrFail();

        $this->assertSame(
            TransactionType::Transfer,
            $transaction->type
        );

        $this->assertSame(
            TransactionStatus::Posted,
            $transaction->status
        );

        $this->assertSame(
            TransactionTransferKind::External,
            $transaction->transferKind()
        );

        $this->assertTrue(
            $transaction->isExternalTransfer()
        );

        $this->assertSame(
            [
                'name' => 'Rizki Ramadhan',
                'institution' => 'BCA',
                'account_number' => '1234567890',
            ],
            $transaction->externalDestination()
        );

        $this->assertSame(
            '200000.00',
            $transaction->displayAmount()
        );

        $this->assertSame(
            '297500.00',
            $source->fresh()->cached_balance
        );

        $this->assertSame(
            '100000.00',
            $unrelatedAccount->fresh()->cached_balance
        );

        $this->assertCount(
            2,
            $transaction->entries
        );

        $this->assertSame(
            [$source->id],
            $transaction->entries
                ->pluck('account_id')
                ->unique()
                ->values()
                ->all()
        );

        $this->assertSame(
            '-200000.00',
            $transaction->entries
                ->firstWhere(
                    'role',
                    TransactionEntryRole::Principal
                )
                ?->amount
        );
    }

    public function test_external_transfer_requires_destination_name(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '500000.00',
            'initial_balance' => '500000.00',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::External->value,
                'account_id' => $source->id,
                'amount' => '200000',
                'admin_fee' => '0',
                'occurred_at' => '2026-08-05T12:00',
                'external_destination_name' => '',
            ]);

        $response
            ->assertRedirectToRoute('transactions.create')
            ->assertSessionHasErrors(
                'external_destination_name'
            );

        $this->assertDatabaseCount(
            'transactions',
            0
        );
    }

    public function test_cancelling_external_transfer_restores_source_balance_atomically(): void
    {
        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '500000.00',
            'initial_balance' => '500000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::Transfer->value,
                'transfer_kind' => TransactionTransferKind::External->value,
                'account_id' => $source->id,
                'amount' => '200000',
                'admin_fee' => '2500',
                'occurred_at' => '2026-08-05T13:00',
                'external_destination_name' => 'Penerima Eksternal',
            ])
            ->assertRedirect();

        $transaction = Transaction::query()
            ->firstOrFail();

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
                    'reason' => 'Transfer salah tujuan',
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
