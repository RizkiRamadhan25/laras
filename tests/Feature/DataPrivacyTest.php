<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Account;
use App\Models\Activity;
use App\Models\FinanceCategory;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class DataPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD =
        'Current#Pass123';

    public function test_user_can_download_password_protected_zip_archive(): void
    {
        Storage::fake('public');

        $user = $this->user(
            'Pengguna Ekspor'
        );

        $photoPath =
            'profile-photos/'
            .$user->id
            .'/profile.webp';

        Storage::disk('public')->put(
            $photoPath,
            'profile-photo-content'
        );

        $user->forceFill([
            'profile_photo_path' => $photoPath,
        ])->save();

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
                ),
                [
                    'export_current_password' => self::PASSWORD,
                ]
            );

        $response
            ->assertOk()
            ->assertDownload();

        $archivePath = $response
            ->getFile()
            ->getPathname();

        $zip = new ZipArchive;

        $this->assertTrue(
            $zip->open($archivePath) === true
        );

        try {
            $this->assertNotFalse(
                $zip->locateName(
                    'manifest.json'
                )
            );

            $this->assertNotFalse(
                $zip->locateName(
                    'finance/accounts.json'
                )
            );

            $this->assertNotFalse(
                $zip->locateName(
                    'files/profile-photo.webp'
                )
            );

            $manifest = (string) $zip
                ->getFromName(
                    'manifest.json'
                );

            $accounts = (string) $zip
                ->getFromName(
                    'finance/accounts.json'
                );

            $this->assertStringContainsString(
                $user->email,
                $manifest
            );

            $this->assertStringContainsString(
                'BCA Ekspor',
                $accounts
            );

            $this->assertStringNotContainsString(
                $otherUser->email,
                $manifest
            );

            $this->assertStringNotContainsString(
                'Rekening Pengguna Lain',
                $accounts
            );

            $this->assertStringNotContainsString(
                $user->password,
                $manifest.$accounts
            );
        } finally {
            $zip->close();
            @unlink($archivePath);
        }
    }

    public function test_wrong_password_cannot_export_personal_data(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->from(route('settings.index'))
            ->post(
                route(
                    'settings.data.export'
                ),
                [
                    'export_current_password' => 'Wrong#Password123',
                ]
            )
            ->assertRedirect(
                route('settings.index')
                .'#data-privacy'
            )
            ->assertSessionHasErrors(
                'export_current_password'
            );
    }

    public function test_budget_alert_events_are_included_in_export(): void
    {
        $user = $this->user();
        $category = $this->category(
            $user
        );

        $budget = app(BudgetService::class)
            ->create(
                $user,
                $category,
                [
                    'name' => 'Anggaran Ekspor',
                    'amount' => '1000000.00',
                    'period_type' => BudgetPeriodType::Monthly
                        ->value,
                    'warning_threshold_percent' => '80.00',
                    'start_date' => now()
                        ->startOfMonth()
                        ->toDateString(),
                ]
            );

        $period = $budget
            ->periods()
            ->firstOrFail();

        DB::table(
            'budget_alert_events'
        )->insert([
            'budget_period_id' => $period->id,
            'alert_level' => 'warning',
            'notified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'settings.data.export'
                ),
                [
                    'export_current_password' => self::PASSWORD,
                ]
            );

        $archivePath = $response
            ->getFile()
            ->getPathname();

        $zip = new ZipArchive;
        $zip->open($archivePath);

        try {
            $events = (string) $zip
                ->getFromName(
                    'finance/budget-alert-events.json'
                );

            $this->assertStringContainsString(
                'warning',
                $events
            );
        } finally {
            $zip->close();
            @unlink($archivePath);
        }
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
                    'delete_current_password' => 'Wrong#Password123',

                    'confirmation' => 'HAPUS AKUN',
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
                    'delete_current_password' => self::PASSWORD,

                    'confirmation' => 'hapus',
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
            'profile_photo_path' => $photoPath,
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
                    'delete_current_password' => self::PASSWORD,

                    'confirmation' => 'HAPUS AKUN',
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

    public function test_account_deletion_removes_orphaned_authentication_data(): void
    {
        $user = $this->user(
            'Pengguna Dihapus'
        );

        $otherUser = $this->user(
            'Pengguna Dipertahankan'
        );

        $userSessionId = 'session-user';
        $otherSessionId = 'session-other';

        DB::table('sessions')->insert([
            [
                'id' => $userSessionId,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload-user',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => $otherSessionId,
                'user_id' => $otherUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload-other',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $userNotificationId =
            (string) Str::uuid();

        $otherNotificationId =
            (string) Str::uuid();

        DB::table('notifications')->insert([
            [
                'id' => $userNotificationId,
                'type' => 'Tests\\Notification',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => '{}',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $otherNotificationId,
                'type' => 'Tests\\Notification',
                'notifiable_type' => $otherUser->getMorphClass(),
                'notifiable_id' => $otherUser->id,
                'data' => '{}',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table(
            'password_reset_tokens'
        )->insert([
            [
                'email' => $user->email,
                'token' => 'token-user',
                'created_at' => now(),
            ],
            [
                'email' => $otherUser->email,
                'token' => 'token-other',
                'created_at' => now(),
            ],
        ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' => self::PASSWORD,

                    'confirmation' => 'HAPUS AKUN',
                ]
            )
            ->assertRedirectToRoute(
                'login'
            );

        $this->assertDatabaseMissing(
            'sessions',
            [
                'id' => $userSessionId,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'id' => $userNotificationId,
            ]
        );

        $this->assertDatabaseMissing(
            'password_reset_tokens',
            [
                'email' => $user->email,
            ]
        );

        $this->assertDatabaseHas(
            'sessions',
            [
                'id' => $otherSessionId,
                'user_id' => $otherUser->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' => $otherNotificationId,
                'notifiable_id' => $otherUser->id,
            ]
        );

        $this->assertDatabaseHas(
            'password_reset_tokens',
            [
                'email' => $otherUser->email,
            ]
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

        $otherAccount = Account::factory()
            ->create([
                'user_id' => $otherUser->id,
            ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'settings.account.destroy'
                ),
                [
                    'delete_current_password' => self::PASSWORD,

                    'confirmation' => 'HAPUS AKUN',
                ]
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $otherUser->id,
            ]
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $otherAccount->id,
                'user_id' => $otherUser->id,
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

    private function category(
        User $user
    ): FinanceCategory {
        $category = new FinanceCategory;

        $category->forceFill([
            'user_id' => $user->id,
            'flow_type' => 'expense',
            'name' => 'Makanan',
            'icon' => 'wallet-cards',
            'color' => '#2563EB',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $category->save();

        return $category;
    }
}
