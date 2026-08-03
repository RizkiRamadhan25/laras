<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarAndLoadingScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_and_sidebar_render_the_same_versioned_profile_photo(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $path = 'profile-photos/'
            .$user->id
            .'/profile.webp';

        Storage::disk('public')->put(
            $path,
            'profile-photo'
        );

        $user->forceFill([
            'profile_photo_path' => $path,
        ])->save();

        $user->refresh();

        $url = $user->profilePhotoUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString(
            '?v=',
            $url
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                'data-laras-avatar',
                false
            );

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $response->getContent(),
                e($url)
            )
        );
    }

    public function test_avatar_falls_back_to_user_initials_without_a_photo(): void
    {
        $user = $this->user([
            'name' => 'Rizki Ramadhan',
        ]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('RR')
            ->assertSee(
                'data-laras-avatar-fallback',
                false
            );
    }

    public function test_settings_page_uses_the_shared_avatar_component(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'data-laras-avatar',
                false
            );
    }

    public function test_authenticated_layout_contains_the_initial_loading_screen(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                'data-laras-loading-screen',
                false
            )
            ->assertSee(
                'Menyelaraskan ruangmu...'
            );
    }

    public function test_auth_layout_contains_the_initial_loading_screen(): void
    {
        $this
            ->get(route('login'))
            ->assertOk()
            ->assertSee(
                'data-laras-loading-screen',
                false
            )
            ->assertSee(
                'laras:intro-shown:v1',
                false
            );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(
        array $attributes = []
    ): User {
        $user = User::factory()->create(
            array_merge(
                [
                    'onboarding_completed_at' => now(),
                    'is_active' => true,
                ],
                $attributes
            )
        );

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
