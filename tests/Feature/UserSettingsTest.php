<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_settings_page(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'Sesuaikan Laras dengan kebutuhanmu.'
            )
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_user_can_update_profile_name(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.profile.update'
                ),
                [
                    'name' =>
                        'Rizki Ramadhan',
                ]
            )
            ->assertRedirectToRoute(
                'settings.index'
            )
            ->assertSessionHas('status');

        $this->assertSame(
            'Rizki Ramadhan',
            $user->fresh()->name
        );
    }

    public function test_user_can_update_preferences(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.preferences.update'
                ),
                [
                    'timezone' =>
                        'Asia/Makassar',

                    'date_format' =>
                        'Y-m-d',

                    'time_format' =>
                        'h:i A',

                    'currency_code' =>
                        'USD',

                    'week_starts_on' =>
                        0,
                ]
            )
            ->assertRedirectToRoute(
                'settings.index'
            )
            ->assertSessionHas('status');

        $preference = $user
            ->preference()
            ->firstOrFail();

        $this->assertSame(
            'Asia/Makassar',
            $preference->timezone
        );

        $this->assertSame(
            'Y-m-d',
            $preference->date_format
        );

        $this->assertSame(
            'h:i A',
            $preference->time_format
        );

        $this->assertSame(
            'USD',
            $preference->currency_code
        );

        $this->assertSame(
            0,
            $preference->week_starts_on
        );
    }

    public function test_invalid_preferences_are_rejected(): void
    {
        $user = $this->user();

        $originalPreference = $user
            ->preference()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->patch(
                route(
                    'settings.preferences.update'
                ),
                [
                    'timezone' =>
                        'Invalid/Timezone',

                    'date_format' =>
                        'invalid',

                    'time_format' =>
                        'invalid',

                    'currency_code' =>
                        'INVALID',

                    'week_starts_on' =>
                        9,
                ]
            )
            ->assertRedirect(
                route('settings.index')
            )
            ->assertSessionHasErrors([
                'timezone',
                'date_format',
                'time_format',
                'currency_code',
                'week_starts_on',
            ]);

        $freshPreference =
            $originalPreference->fresh();

        $this->assertSame(
            'Asia/Jakarta',
            $freshPreference->timezone
        );

        $this->assertSame(
            'IDR',
            $freshPreference->currency_code
        );
    }

    public function test_updating_settings_does_not_change_another_user(): void
    {
        $user = $this->user(
            name: 'Pengguna utama'
        );

        $otherUser = $this->user(
            name: 'Pengguna lain'
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.profile.update'
                ),
                [
                    'name' =>
                        'Nama diperbarui',
                ]
            )
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.preferences.update'
                ),
                [
                    'timezone' =>
                        'Asia/Jayapura',

                    'date_format' =>
                        'd-m-Y',

                    'time_format' =>
                        'H:i',

                    'currency_code' =>
                        'SGD',

                    'week_starts_on' =>
                        6,
                ]
            )
            ->assertRedirect();

        $this->assertSame(
            'Pengguna lain',
            $otherUser->fresh()->name
        );

        $otherPreference = $otherUser
            ->preference()
            ->firstOrFail();

        $this->assertSame(
            'Asia/Jakarta',
            $otherPreference->timezone
        );

        $this->assertSame(
            'IDR',
            $otherPreference
                ->currency_code
        );
    }

    public function test_preference_is_created_when_missing(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' =>
                now(),

            'is_active' => true,
        ]);

        $this->assertNull(
            $user->preference
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.preferences.update'
                ),
                [
                    'timezone' =>
                        'Asia/Jakarta',

                    'date_format' =>
                        'd/m/Y',

                    'time_format' =>
                        'H:i',

                    'currency_code' =>
                        'IDR',

                    'week_starts_on' =>
                        1,
                ]
            )
            ->assertRedirectToRoute(
                'settings.index'
            );

        $this->assertDatabaseHas(
            'user_preferences',
            [
                'user_id' => $user->id,
                'timezone' => 'Asia/Jakarta',
                'currency_code' => 'IDR',
                'week_starts_on' => 1,
            ]
        );
    }

    private function user(
        string $name = 'Pengguna Laras'
    ): User {
        $user = User::factory()->create([
            'name' => $name,

            'onboarding_completed_at' =>
                now(),

            'is_active' => true,
        ]);

        UserPreference::query()
            ->updateOrCreate(
                [
                    'user_id' =>
                        $user->id,
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
