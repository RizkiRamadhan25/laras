<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $response = $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.photo.update'
                ),
                [
                    'photo' =>
                        UploadedFile::fake()
                            ->image(
                                'profile.jpg',
                                600,
                                600
                            )
                            ->size(500),
                ]
            );

        $response
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            )
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertNotNull(
            $user->profile_photo_path
        );

        Storage::disk('public')
            ->assertExists(
                $user->profile_photo_path
            );
    }

    public function test_new_photo_replaces_old_photo(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $oldPath =
            'profile-photos/'
            .$user->id
            .'/old-profile.jpg';

        Storage::disk('public')->put(
            $oldPath,
            'old-photo'
        );

        $user->forceFill([
            'profile_photo_path' =>
                $oldPath,
        ])->save();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.photo.update'
                ),
                [
                    'photo' =>
                        UploadedFile::fake()
                            ->image(
                                'new-profile.png',
                                800,
                                800
                            )
                            ->size(600),
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            );

        $user->refresh();

        Storage::disk('public')
            ->assertMissing($oldPath);

        Storage::disk('public')
            ->assertExists(
                $user->profile_photo_path
            );
    }

    public function test_user_can_delete_profile_photo(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $path =
            'profile-photos/'
            .$user->id
            .'/profile.webp';

        Storage::disk('public')->put(
            $path,
            'profile-photo'
        );

        $user->forceFill([
            'profile_photo_path' => $path,
        ])->save();

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'settings.photo.destroy'
                )
            )
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            )
            ->assertSessionHas('status');

        $this->assertNull(
            $user
                ->fresh()
                ->profile_photo_path
        );

        Storage::disk('public')
            ->assertMissing($path);
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->patch(
                route(
                    'settings.photo.update'
                ),
                [
                    'photo' =>
                        UploadedFile::fake()
                            ->create(
                                'document.pdf',
                                500,
                                'application/pdf'
                            ),
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            )
            ->assertSessionHasErrors(
                'photo'
            );

        $this->assertNull(
            $user
                ->fresh()
                ->profile_photo_path
        );
    }

    private function user(): User
    {
        $user = User::factory()->create([
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
