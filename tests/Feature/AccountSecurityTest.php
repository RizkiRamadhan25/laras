<?php

namespace Tests\Feature;

use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_PASSWORD =
        'Current#Pass123';

    private const NEW_PASSWORD =
        'NewSecure#456';

    public function test_user_can_view_security_settings(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'Lindungi akun Laras.'
            )
            ->assertSee(
                'Ubah kata sandi'
            )
            ->assertSee(
                'Perangkat dan sesi'
            )
            ->assertSee(
                'Belum ada aktivitas keamanan'
            );
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' =>
                        self::CURRENT_PASSWORD,

                    'password' =>
                        self::NEW_PASSWORD,

                    'password_confirmation' =>
                        self::NEW_PASSWORD,
                ]
            )
            ->assertRedirectToRoute(
                'settings.index'
            )
            ->assertSessionHas('status');

        $freshUser = $user->fresh();

        $this->assertTrue(
            Hash::check(
                self::NEW_PASSWORD,
                $freshUser->password
            )
        );

        $this->assertFalse(
            Hash::check(
                self::CURRENT_PASSWORD,
                $freshUser->password
            )
        );

        $this->assertDatabaseHas(
            'security_events',
            [
                'user_id' => $user->id,

                'type' =>
                    SecurityEventType
                        ::PasswordChanged
                        ->value,
            ]
        );

        /*
         * Perangkat saat ini harus tetap
         * dapat membuka halaman aplikasi.
         */
        $this
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' =>
                        'Wrong#Password123',

                    'password' =>
                        self::NEW_PASSWORD,

                    'password_confirmation' =>
                        self::NEW_PASSWORD,
                ]
            )
            ->assertRedirect(
                route('settings.index')
            )
            ->assertSessionHasErrors(
                'current_password'
            );

        $this->assertTrue(
            Hash::check(
                self::CURRENT_PASSWORD,
                $user->fresh()->password
            )
        );

        $this->assertDatabaseCount(
            'security_events',
            0
        );
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' =>
                        self::CURRENT_PASSWORD,

                    'password' =>
                        self::NEW_PASSWORD,

                    'password_confirmation' =>
                        'Different#Password789',
                ]
            )
            ->assertRedirect(
                route('settings.index')
            )
            ->assertSessionHasErrors(
                'password'
            );

        $this->assertTrue(
            Hash::check(
                self::CURRENT_PASSWORD,
                $user->fresh()->password
            )
        );
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->patch(
                route(
                    'settings.security.password.update'
                ),
                [
                    'current_password' =>
                        self::CURRENT_PASSWORD,

                    'password' => 'password',

                    'password_confirmation' =>
                        'password',
                ]
            )
            ->assertRedirect(
                route('settings.index')
            )
            ->assertSessionHasErrors(
                'password'
            );

        $this->assertTrue(
            Hash::check(
                self::CURRENT_PASSWORD,
                $user->fresh()->password
            )
        );
    }

    public function test_user_can_logout_other_devices(): void
    {
        $user = $this->user();

        $oldHash = $user->password;

        $this
            ->actingAs($user)
            ->post(
                route(
                    'settings.security.sessions.logout-others'
                ),
                [
                    'logout_current_password' =>
                        self::CURRENT_PASSWORD,
                ]
            )
            ->assertRedirectToRoute(
                'settings.index'
            )
            ->assertSessionHas('status');

        $freshUser = $user->fresh();

        /*
         * Password tetap sama, tetapi hash
         * diperbarui untuk membatalkan sesi lain.
         */
        $this->assertTrue(
            Hash::check(
                self::CURRENT_PASSWORD,
                $freshUser->password
            )
        );

        $this->assertNotSame(
            $oldHash,
            $freshUser->password
        );

        $this->assertDatabaseHas(
            'security_events',
            [
                'user_id' => $user->id,

                'type' =>
                    SecurityEventType
                        ::OtherSessionsLoggedOut
                        ->value,
            ]
        );

        $this
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_security_history_only_shows_owned_events(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        SecurityEvent::query()->create([
            'user_id' => $user->id,

            'type' =>
                SecurityEventType
                    ::PasswordChanged,

            'ip_address' =>
                '192.168.1.10',

            'user_agent' =>
                'Browser milik pengguna utama',

            'occurred_at' => now(),
        ]);

        SecurityEvent::query()->create([
            'user_id' => $otherUser->id,

            'type' =>
                SecurityEventType
                    ::PasswordChanged,

            'ip_address' =>
                '192.168.1.20',

            'user_agent' =>
                'Browser milik pengguna lain',

            'occurred_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'Browser milik pengguna utama'
            )
            ->assertDontSee(
                'Browser milik pengguna lain'
            )
            ->assertSee(
                '192.168.1.10'
            )
            ->assertDontSee(
                '192.168.1.20'
            );
    }

    private function user(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make(
                self::CURRENT_PASSWORD
            ),

            'onboarding_completed_at' =>
                now(),

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
