<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD =
        'Current#Pass123';

    public function test_user_can_download_personal_data_archive(): void
    {
        $user = $this->user(
            'Pengguna Ekspor'
        );

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Ekspor',
        ]);

        $otherUser = $this->user(
            'Pengguna Lain'
        );

        Account::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Rekening Pengguna Lain',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'settings.data.export'
                )
            );

        $response
            ->assertOk()
            ->assertStreamed()
            ->assertDownload();

        $content =
            $response->streamedContent();

        $payload = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            $user->email,
            $payload['profile']['email']
        );

        $this->assertSame(
            'BCA Ekspor',
            $payload['finance']
                ['accounts'][0]['name']
        );

        $this->assertStringNotContainsString(
            $otherUser->email,
            $content
        );

        $this->assertStringNotContainsString(
            'Rekening Pengguna Lain',
            $content
        );

        $this->assertStringNotContainsString(
            $user->password,
            $content
        );

        $this->assertStringNotContainsString(
            'remember_token',
            $content
        );
    }

    public function test_wrong_password_cannot_delete_account(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' =>
                        'Wrong#Password123',

                    'confirmation' =>
                        'HAPUS AKUN',
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#data-privacy'
            )
            ->assertSessionHasErrors(
                'delete_current_password'
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
            ]
        );
    }

    public function test_confirmation_phrase_must_match(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' =>
                        self::PASSWORD,

                    'confirmation' =>
                        'hapus',
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#data-privacy'
            )
            ->assertSessionHasErrors(
                'confirmation'
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
            ]
        );
    }

    public function test_user_can_permanently_delete_account_and_owned_data(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $photoPath =
            'profile-photos/'
            .$user->id
            .'/profile.jpg';

        Storage::disk('public')->put(
            $photoPath,
            'profile-photo'
        );

        $user->forceFill([
            'profile_photo_path' =>
                $photoPath,
        ])->save();

        $account = Account::factory()
            ->create([
                'user_id' => $user->id,
            ]);

        $activity = Activity::factory()
            ->create([
                'user_id' => $user->id,
            ]);

        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'type' => 'password_changed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'occurred_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' =>
                        self::PASSWORD,

                    'confirmation' =>
                        'HAPUS AKUN',
                ]
            )
            ->assertRedirectToRoute(
                'login'
            )
            ->assertSessionHas('status');

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            [
                'id' => $user->id,
            ]
        );

        $this->assertDatabaseMissing(
            'user_preferences',
            [
                'user_id' => $user->id,
            ]
        );

        $this->assertDatabaseMissing(
            'accounts',
            [
                'id' => $account->id,
            ]
        );

        $this->assertDatabaseMissing(
            'activities',
            [
                'id' => $activity->id,
            ]
        );

        $this->assertDatabaseMissing(
            'security_events',
            [
                'user_id' => $user->id,
            ]
        );

        Storage::disk('public')
            ->assertMissing(
                $photoPath
            );
    }

    public function test_deleting_account_does_not_affect_other_user(): void
    {
        $user = $this->user(
            'Pengguna Dihapus'
        );

        $otherUser = $this->user(
            'Pengguna Dipertahankan'
        );

        $otherAccount =
            Account::factory()->create([
                'user_id' =>
                    $otherUser->id,

                'name' =>
                    'Rekening Tetap Ada',
            ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' =>
                        self::PASSWORD,

                    'confirmation' =>
                        'HAPUS AKUN',
                ]
            )
            ->assertRedirectToRoute(
                'login'
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $otherUser->id,

                'name' =>
                    'Pengguna Dipertahankan',
            ]
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $otherAccount->id,

                'user_id' =>
                    $otherUser->id,
            ]
        );
    }

    private function user(
        string $name = 'Pengguna Laras'
    ): User {
        $user = User::factory()->create([
            'name' => $name,

            'password' => Hash::make(
                self::PASSWORD
            ),

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
