<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_store_onboarding_preferences(): void
    {
        $response = $this->post(
            route('onboarding.preferences.store'),
            $this->validPreferenceData()
        );

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_incomplete_user_can_store_preferences(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'onboarding_completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('onboarding.preferences.store'),
                $this->validPreferenceData()
            );

        $response
            ->assertRedirectToRoute('onboarding.accounts')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Rizki Ramadhan',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        $this->assertNull(
            $user->fresh()->onboarding_completed_at
        );
    }

    public function test_existing_preferences_are_updated_without_duplicate(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'USD',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'Y-m-d',
            'time_format' => 'h:i A',
            'week_starts_on' => 7,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('onboarding.preferences.store'),
                $this->validPreferenceData()
            );

        $response->assertRedirectToRoute('onboarding.accounts');

        $this->assertSame(
            1,
            UserPreference::query()
                ->where('user_id', $user->id)
                ->count()
        );

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'currency_code' => 'IDR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);
    }

    public function test_invalid_preferences_are_rejected(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('onboarding.preferences.edit'))
            ->post(route('onboarding.preferences.store'), [
                'name' => '',
                'locale' => 'unknown',
                'currency_code' => 'ABC',
                'timezone' => 'Invalid/Timezone',
                'date_format' => 'invalid',
                'time_format' => 'invalid',
                'week_starts_on' => 4,
            ]);

        $response
            ->assertRedirectToRoute('onboarding.preferences.edit')
            ->assertSessionHasErrors([
                'name',
                'locale',
                'currency_code',
                'timezone',
                'date_format',
                'time_format',
                'week_starts_on',
            ]);

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
        ]);
    }

    public function test_onboarding_root_redirects_to_accounts_when_preferences_exist(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
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

        $response = $this
            ->actingAs($user)
            ->get(route('onboarding.show'));

        $response->assertRedirectToRoute('onboarding.accounts');
    }

    public function test_accounts_step_requires_preferences(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('onboarding.accounts'));

        $response
            ->assertRedirectToRoute('onboarding.preferences.edit')
            ->assertSessionHas('warning');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPreferenceData(): array
    {
        return [
            'name' => 'Rizki Ramadhan',
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ];
    }
}
