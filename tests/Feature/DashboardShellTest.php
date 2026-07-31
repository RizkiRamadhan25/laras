<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_user_can_view_dashboard_shell(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'type' => AccountType::Bank,
            'initial_balance' => '1500000.50',
            'cached_balance' => '1500000.50',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewIs('dashboard.index')
            ->assertSee('Dashboard')
            ->assertSee('BCA Utama')
            ->assertSee('Ringkasan rekening')
            ->assertSee('Aktivitas')
            ->assertSee('Prioritas')
            ->assertSee('Keuangan');
    }

    public function test_dashboard_calculates_only_active_account_balances(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '1000000.25',
            'is_active' => true,
        ]);

        Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '500000.25',
            'is_active' => true,
        ]);

        Account::factory()->create([
            'user_id' => $user->id,
            'cached_balance' => '9000000.00',
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas(
                'totalBalance',
                '1500000.50'
            );

        $response->assertViewHas(
            'accounts',
            fn ($accounts): bool => $accounts->count() === 2
        );
    }

    public function test_dashboard_uses_user_timezone_and_preference(): void
    {
        $user = $this->completedUser(
            timezone: 'Asia/Jakarta'
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('currentDate')
            ->assertViewHas('greeting');
    }

    private function completedUser(
        string $timezone = 'Asia/Jakarta'
    ): User {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => $timezone,
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        return $user;
    }
}
