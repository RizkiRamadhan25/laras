<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_store_onboarding_accounts(): void
    {
        $response = $this->post(
            route('onboarding.accounts.store'),
            $this->validAccountsData()
        );

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_preferences_must_exist_before_accounts_are_stored(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('onboarding.accounts.store'),
                $this->validAccountsData()
            );

        $response
            ->assertRedirectToRoute('onboarding.preferences.edit')
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('accounts', 0);

        $this->assertNull(
            $user->fresh()->onboarding_completed_at
        );
    }

    public function test_user_can_store_initial_accounts_and_complete_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->createPreference($user);

        $response = $this
            ->actingAs($user)
            ->post(
                route('onboarding.accounts.store'),
                $this->validAccountsData()
            );

        $response
            ->assertRedirectToRoute('dashboard')
            ->assertSessionHas('status');

        $this->assertDatabaseCount('accounts', 3);

        $this->assertNotNull(
            $user->fresh()->onboarding_completed_at
        );

        $bca = Account::query()
            ->where('user_id', $user->id)
            ->where('name', 'BCA Utama')
            ->firstOrFail();

        $this->assertSame('1500000.50', $bca->initial_balance);
        $this->assertSame('1500000.50', $bca->cached_balance);
        $this->assertSame('IDR', $bca->currency_code);
        $this->assertSame(1, $bca->sort_order);
    }

    public function test_accounts_are_stored_in_submitted_order(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->createPreference($user);

        $this
            ->actingAs($user)
            ->post(
                route('onboarding.accounts.store'),
                $this->validAccountsData()
            );

        $names = $user->accounts()
            ->pluck('name')
            ->all();

        $this->assertSame([
            'BCA Utama',
            'SeaBank',
            'Uang Tunai',
        ], $names);
    }

    public function test_duplicate_account_names_are_rejected(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->createPreference($user);

        $data = $this->validAccountsData();

        $data['accounts'][1]['name'] = 'bca utama';

        $response = $this
            ->actingAs($user)
            ->from(route('onboarding.accounts'))
            ->post(
                route('onboarding.accounts.store'),
                $data
            );

        $response
            ->assertRedirectToRoute('onboarding.accounts')
            ->assertSessionHasErrors([
                'accounts.1.name',
            ]);

        $this->assertDatabaseCount('accounts', 0);

        $this->assertNull(
            $user->fresh()->onboarding_completed_at
        );
    }

    public function test_invalid_balance_prevents_all_accounts_from_being_stored(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->createPreference($user);

        $data = $this->validAccountsData();

        $data['accounts'][1]['initial_balance'] = '-1000';

        $response = $this
            ->actingAs($user)
            ->from(route('onboarding.accounts'))
            ->post(
                route('onboarding.accounts.store'),
                $data
            );

        $response
            ->assertRedirectToRoute('onboarding.accounts')
            ->assertSessionHasErrors([
                'accounts.1.initial_balance',
            ]);

        $this->assertDatabaseCount('accounts', 0);

        $this->assertNull(
            $user->fresh()->onboarding_completed_at
        );
    }

    public function test_existing_incomplete_accounts_are_replaced(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->createPreference($user);

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Rekening Lama',
        ]);

        $this
            ->actingAs($user)
            ->post(
                route('onboarding.accounts.store'),
                $this->validAccountsData()
            );

        $this->assertDatabaseMissing('accounts', [
            'user_id' => $user->id,
            'name' => 'Rekening Lama',
        ]);

        $this->assertSame(
            3,
            $user->accounts()->count()
        );
    }

    public function test_completed_user_cannot_submit_onboarding_again(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $this->createPreference($user);

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Rekening Tersimpan',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('onboarding.accounts.store'),
                $this->validAccountsData()
            );

        $response->assertRedirectToRoute('dashboard');

        $this->assertSame(
            1,
            $user->accounts()->count()
        );

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Rekening Tersimpan',
        ]);
    }

    private function createPreference(User $user): void
    {
        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validAccountsData(): array
    {
        return [
            'accounts' => [
                [
                    'name' => 'BCA Utama',
                    'type' => 'bank',
                    'institution' => 'BCA',
                    'initial_balance' => '1500000.50',
                    'account_number_last_four' => '2509',
                    'color' => '#2563EB',
                ],
                [
                    'name' => 'SeaBank',
                    'type' => 'bank',
                    'institution' => 'SeaBank',
                    'initial_balance' => '500000',
                    'account_number_last_four' => '1025',
                    'color' => '#0EA5E9',
                ],
                [
                    'name' => 'Uang Tunai',
                    'type' => 'cash',
                    'institution' => null,
                    'initial_balance' => '150000',
                    'account_number_last_four' => null,
                    'color' => '#16A34A',
                ],
            ],
        ];
    }
}
