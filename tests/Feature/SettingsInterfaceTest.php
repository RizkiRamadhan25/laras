<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD =
        'Current#Pass123';

    public function test_settings_page_contains_complete_section_navigation(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'Navigasi pengaturan',
                false
            )
            ->assertSee(
                'href="#profile"',
                false
            )
            ->assertSee(
                'href="#preferences"',
                false
            )
            ->assertSee(
                'href="#security"',
                false
            )
            ->assertSee(
                'href="#data-privacy"',
                false
            )
            ->assertSee(
                'data-track-unsaved',
                false
            )
            ->assertSee(
                'data-settings-section',
                false
            );
    }

    public function test_profile_validation_error_returns_to_settings(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(
                route('settings.index')
                .'#profile'
            )
            ->patch(
                route(
                    'settings.profile.update'
                ),
                [
                    'name' => 'A',
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            )
            ->assertSessionHasErrors(
                'name'
            );
    }

    public function test_security_error_does_not_trigger_preference_error_summary(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->followingRedirects()
            ->from(
                route('settings.index')
                .'#security'
            )
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' => 'Wrong#Password123',

                    'password' => 'NewSecure#456',

                    'password_confirmation' => 'NewSecure#456',
                ]
            )
            ->assertOk()
            ->assertSee(
                'Kata sandi saat ini tidak sesuai.'
            )
            ->assertDontSee(
                'Periksa kembali preferensi yang dipilih.'
            );
    }

    public function test_account_deletion_error_does_not_trigger_preference_error_summary(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->followingRedirects()
            ->from(
                route('settings.index')
                .'#data-privacy'
            )
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' => self::PASSWORD,

                    'confirmation' => 'SALAH',
                ]
            )
            ->assertOk()
            ->assertSee(
                'Ketik HAPUS AKUN untuk mengonfirmasi penghapusan.'
            )
            ->assertDontSee(
                'Periksa kembali preferensi yang dipilih.'
            );
    }

    private function user(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make(
                self::PASSWORD
            ),

            'onboarding_completed_at' => now(),

            'is_active' => true,
        ]);

        UserPreference::query()
            ->updateOrCreate(
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
