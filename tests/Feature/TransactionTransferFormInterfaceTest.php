<?php

namespace Tests\Feature;

use App\Enums\TransactionTransferKind;
use App\Models\Account;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferFormInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_form_exposes_internal_and_external_destination_fields(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.create',
                    ['type' => 'transfer']
                )
            )
            ->assertOk()
            ->assertSee(
                'data-transaction-transfer-form',
                false
            )
            ->assertSee(
                'data-transfer-kind-selector',
                false
            )
            ->assertSee(
                'name="transfer_kind"',
                false
            )
            ->assertSee(
                'value="internal"',
                false
            )
            ->assertSee(
                'value="external"',
                false
            )
            ->assertSee(
                'data-transfer-internal-fields',
                false
            )
            ->assertSee(
                'name="destination_account_id"',
                false
            )
            ->assertSee(
                'data-transfer-external-fields',
                false
            )
            ->assertSee(
                'name="external_destination_name"',
                false
            )
            ->assertSee(
                'name="external_destination_institution"',
                false
            )
            ->assertSee(
                'name="external_destination_account_number"',
                false
            )
            ->assertSee(
                'data-transfer-impact-summary',
                false
            );
    }

    public function test_external_transfer_validation_preserves_selected_destination_input(): void
    {
        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->from(
                route(
                    'transactions.create',
                    ['type' => 'transfer']
                )
            )
            ->post(
                route('transactions.store'),
                [
                    'type' => 'transfer',
                    'transfer_kind' => TransactionTransferKind::External->value,
                    'account_id' => $account->id,
                    'amount' => '150000',
                    'admin_fee' => '2500',
                    'occurred_at' => '2026-08-05T17:30',
                    'external_destination_name' => '',
                    'external_destination_institution' => 'BCA',
                    'external_destination_account_number' => '1234567890',
                ]
            )
            ->assertRedirectToRoute(
                'transactions.create',
                ['type' => 'transfer']
            )
            ->assertSessionHasErrors(
                'external_destination_name'
            )
            ->assertSessionHasInput(
                'transfer_kind',
                TransactionTransferKind::External->value
            )
            ->assertSessionHasInput(
                'external_destination_institution',
                'BCA'
            )
            ->assertSessionHasInput(
                'external_destination_account_number',
                '1234567890'
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
