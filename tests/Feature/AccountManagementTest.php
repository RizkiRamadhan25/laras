<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_user_can_view_own_accounts(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('accounts.index'));

        $response
            ->assertOk()
            ->assertSee('BCA Utama')
            ->assertSee('Rekening dan saldo');
    }

    public function test_user_can_create_account(): void
    {
        $user = $this->completedUser();

        $response = $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'SeaBank',
                'type' => 'bank',
                'institution' => 'SeaBank',
                'initial_balance' => '750000.50',
                'account_number_last_four' => '2509',
                'color' => '#0EA5E9',
            ]);

        $response
            ->assertRedirectToRoute('accounts.index')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'SeaBank',
            'currency_code' => 'IDR',
            'initial_balance' => '750000.50',
            'cached_balance' => '750000.50',
        ]);
    }

    public function test_duplicate_name_is_rejected_case_insensitively(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('accounts.create'))
            ->post(route('accounts.store'), [
                'name' => 'bca utama',
                'type' => 'bank',
                'institution' => 'BCA',
                'initial_balance' => '0',
                'account_number_last_four' => null,
                'color' => '#2563EB',
            ]);

        $response
            ->assertRedirectToRoute('accounts.create')
            ->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            Account::query()
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_updating_initial_balance_shifts_cached_balance_by_difference(): void
    {
        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'initial_balance' => '1000000.00',
            'cached_balance' => '1250000.00',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('accounts.update', $account->id), [
                'name' => 'BCA Utama',
                'type' => 'bank',
                'institution' => 'BCA',
                'initial_balance' => '1500000.00',
                'account_number_last_four' => '2509',
                'color' => '#2563EB',
            ]);

        $response->assertRedirectToRoute('accounts.index');

        $account->refresh();

        $this->assertSame(
            '1500000.00',
            $account->initial_balance
        );

        $this->assertSame(
            '1750000.00',
            $account->cached_balance
        );
    }

    public function test_user_cannot_edit_another_users_account(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('accounts.edit', $account->id));

        $response->assertNotFound();
    }

    public function test_account_can_be_archived_and_restored(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'sort_order' => 1,
        ]);

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'SeaBank',
            'sort_order' => 2,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account->id))
            ->assertRedirectToRoute('accounts.index');

        $this->assertSoftDeleted('accounts', [
            'id' => $account->id,
        ]);

        $this
            ->actingAs($user)
            ->patch(route('accounts.restore', $account->id))
            ->assertRedirectToRoute('accounts.index');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'is_active' => true,
            'deleted_at' => null,
        ]);
    }

    public function test_last_active_account_cannot_be_archived(): void
    {
        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account->id));

        $response
            ->assertRedirectToRoute('accounts.index')
            ->assertSessionHas('warning');

        $this->assertNotSoftDeleted('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_account_order_can_be_changed(): void
    {
        $user = $this->completedUser();

        $first = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Pertama',
            'sort_order' => 1,
        ]);

        $second = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kedua',
            'sort_order' => 2,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(
                route('accounts.move', $second->id),
                [
                    'direction' => 'up',
                ]
            );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Urutan rekening berhasil diperbarui.',
                'account_id' => $second->id,
                'direction' => 'up',
                'ordered_account_ids' => [
                    $second->id,
                    $first->id,
                ],
            ]);

        $this->assertSame(
            [
                $second->id,
                $first->id,
            ],
            $user->accounts()->pluck('id')->all()
        );
    }

    private function completedUser(): User
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
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

    public function test_account_used_by_active_subscription_cannot_be_archived(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Rekening Cadangan',
            'sort_order' => 1,
        ]);

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Langganan',
            'sort_order' => 2,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account->id));

        $response
            ->assertRedirectToRoute('accounts.index')
            ->assertSessionHas('warning');

        $this->assertNotSoftDeleted('accounts', [
            'id' => $account->id,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'is_active' => true,
            'deleted_at' => null,
        ]);
    }

    public function test_account_used_only_by_cancelled_subscription_can_be_archived(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Rekening Cadangan',
            'sort_order' => 1,
        ]);

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Rekening Lama',
            'sort_order' => 2,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account->id))
            ->assertRedirectToRoute('accounts.index');

        $this->assertSoftDeleted('accounts', [
            'id' => $account->id,
        ]);
    }
}
