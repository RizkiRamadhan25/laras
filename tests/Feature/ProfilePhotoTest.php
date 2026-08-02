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

    public function test_uploaded_photo_is_reencoded_and_normalized(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $photo = UploadedFile::fake()
            ->image(
                'profile.jpg',
                1800,
                1200
            )
            ->size(900);

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.photo.update'
                ),
                [
                    'photo' => $photo,
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            )
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertNotNull(
            $user->profile_photo_path
        );

        $this->assertStringEndsWith(
            '.webp',
            $user->profile_photo_path
        );

        Storage::disk('public')
            ->assertExists(
                $user->profile_photo_path
            );

        [$width, $height] = getimagesize(
            Storage::disk('public')->path(
                $user->profile_photo_path
            )
        );

        $this->assertSame(512, $width);
        $this->assertSame(512, $height);
    }

    public function test_reencoding_removes_trailing_metadata_payload(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $photo = UploadedFile::fake()
            ->image(
                'profile.jpg',
                600,
                600
            );

        file_put_contents(
            $photo->getPathname(),
            'PRIVATE-EXIF-MARKER',
            FILE_APPEND
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.photo.update'
                ),
                [
                    'photo' => $photo,
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#profile'
            );

        $stored = Storage::disk('public')
            ->get(
                $user
                    ->fresh()
                    ->profile_photo_path
            );

        $this->assertStringNotContainsString(
            'PRIVATE-EXIF-MARKER',
            $stored
        );
    }

    public function test_new_photo_replaces_old_photo(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $oldPath =
            'profile-photos/'
            .$user->id
            .'/old-profile.webp';

        Storage::disk('public')->put(
            $oldPath,
            'old-photo'
        );

        $user->forceFill([
            'profile_photo_path' => $oldPath,
        ])->save();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'settings.photo.update'
                ),
                [
                    'photo' => UploadedFile::fake()
                        ->image(
                            'new-profile.png',
                            800,
                            800
                        ),
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
                    'photo' => UploadedFile::fake()
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
