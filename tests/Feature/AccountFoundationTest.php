<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_multiple_accounts(): void
    {
        $user = User::factory()->create();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'sort_order' => 1,
        ]);

        Account::factory()->cash()->create([
            'user_id' => $user->id,
            'name' => 'Uang Tunai',
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $user->accounts);
    }

    public function test_account_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $account->user->is($user)
        );
    }

    public function test_account_type_is_cast_to_enum(): void
    {
        $account = Account::factory()->create([
            'type' => AccountType::Bank,
        ]);

        $this->assertSame(
            AccountType::Bank,
            $account->type
        );
    }

    public function test_money_values_are_stored_as_decimal_strings(): void
    {
        $account = Account::factory()->create([
            'initial_balance' => '1500000.50',
            'cached_balance' => '1500000.50',
        ]);

        $account->refresh();

        $this->assertSame(
            '1500000.50',
            $account->initial_balance
        );

        $this->assertSame(
            '1500000.50',
            $account->cached_balance
        );
    }

    public function test_deleted_account_is_soft_deleted(): void
    {
        $account = Account::factory()->create();

        $account->delete();

        $this->assertSoftDeleted('accounts', [
            'id' => $account->id,
        ]);

        $this->assertNull(
            Account::query()->find($account->id)
        );

        $this->assertNotNull(
            Account::withTrashed()->find($account->id)
        );
    }

    public function test_deleting_user_removes_owned_accounts(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->forceDelete();

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }
}
