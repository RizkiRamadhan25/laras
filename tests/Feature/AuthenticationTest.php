<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_active_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'Password!12345',
            'is_active' => true,
            'last_login_at' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password!12345',
        ]);

        $response->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password!12345',
            'is_active' => true,
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'PasswordYangSalah!123',
            ]);

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'Password!12345',
            'is_active' => false,
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'Password!12345',
            ]);

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'Password!12345',
            'is_active' => true,
        ]);

        $user->delete();

        $response = $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'Password!12345',
            ]);

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_successful_login_updates_last_login_time(): void
    {
        $user = User::factory()->create([
            'password' => 'Password!12345',
            'is_active' => true,
            'last_login_at' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password!12345',
        ]);

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
    }

    public function test_authenticated_user_is_redirected_from_home_to_dashboard(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/');

        $response->assertRedirectToRoute('dashboard');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect('/');

        $this->assertGuest();
    }
}
