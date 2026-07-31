<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_onboarding(): void
    {
        $response = $this->get(route('onboarding.show'));

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_incomplete_user_can_view_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('onboarding.show'));

        $response
            ->assertOk()
            ->assertViewIs('onboarding.show');
    }

    public function test_incomplete_user_is_redirected_from_dashboard_to_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirectToRoute('onboarding.show');
    }

    public function test_completed_user_cannot_reopen_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('onboarding.show'));

        $response->assertRedirectToRoute('dashboard');
    }

    public function test_completed_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
    }
}
