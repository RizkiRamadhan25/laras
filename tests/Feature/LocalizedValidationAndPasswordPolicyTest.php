<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalizedValidationAndPasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_PASSWORD =
        'Current#Pass123';

    public function test_unknown_email_uses_indonesian_email_error(): void
    {
        config()->set(
            'laras.authentication.detailed_errors',
            true
        );

        $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'tidak-ada@example.com',
                'password' => 'Password#123',
            ])
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'Email tidak terdaftar.',
            ]);

        $this->assertGuest();
    }

    public function test_wrong_password_uses_indonesian_password_error(): void
    {
        config()->set(
            'laras.authentication.detailed_errors',
            true
        );

        $user = User::factory()->create([
            'password' => Hash::make(
                self::CURRENT_PASSWORD
            ),
            'is_active' => true,
        ]);

        $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'Wrong#Password123',
            ])
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'password' => 'Kata sandi yang dimasukkan tidak sesuai.',
            ]);

        $this->assertGuest();
    }

    public function test_weak_password_reports_each_missing_requirement_in_indonesian(): void
    {
        $user = $this->user();

        $response = $this
            ->actingAs($user)
            ->from(
                route('settings.index')
                .'#security'
            )
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' => self::CURRENT_PASSWORD,
                    'password' => 'abcdefgh',
                    'password_confirmation' => 'abcdefgh',
                ]
            );

        $response
            ->assertRedirect(
                route('settings.index')
                .'#security'
            )
            ->assertSessionHasErrors('password');

        $messages = session('errors')
            ->get('password');

        $this->assertContains(
            'Kata sandi baru harus mengandung huruf besar dan huruf kecil.',
            $messages
        );

        $this->assertContains(
            'Kata sandi baru harus mengandung minimal satu angka.',
            $messages
        );

        $this->assertContains(
            'Kata sandi baru harus mengandung minimal satu simbol.',
            $messages
        );
    }

    public function test_short_password_reports_minimum_length_in_indonesian(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(
                route('settings.index')
                .'#security'
            )
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' => self::CURRENT_PASSWORD,
                    'password' => 'Aa#1',
                    'password_confirmation' => 'Aa#1',
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#security'
            )
            ->assertSessionHasErrors([
                'password' => 'Kata sandi baru minimal harus terdiri dari 8 karakter.',
            ]);
    }

    public function test_password_requirements_are_rendered_without_error_dots(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(
                route('settings.index')
                .'#security'
            )
            ->assertOk()
            ->assertSee(
                'data-laras-password-requirements',
                false
            )
            ->assertSee('Minimal 8 karakter')
            ->assertSee('Mengandung huruf besar')
            ->assertSee('Mengandung huruf kecil')
            ->assertSee('Mengandung angka')
            ->assertSee('Mengandung simbol')
            ->assertDontSee(
                'laras-field__error-dot',
                false
            );
    }

    private function user(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make(
                self::CURRENT_PASSWORD
            ),
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);

        UserPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'locale' => 'id',
                'currency_code' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'week_starts_on' => 1,
            ]
        );

        return $user;
    }
}
