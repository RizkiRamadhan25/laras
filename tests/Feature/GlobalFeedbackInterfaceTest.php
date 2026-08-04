<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class GlobalFeedbackInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_renders_session_messages_as_toasts(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->withSession([
                'status' => 'Perubahan berhasil disimpan.',
                'warning' => 'Periksa kembali pilihanmu.',
            ])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-laras-toast-region', false)
            ->assertSee('laras-initial-toasts', false)
            ->assertSee('Perubahan berhasil disimpan.')
            ->assertSee('Periksa kembali pilihanmu.')
            ->assertSee('data-laras-confirm-dialog', false);
    }

    public function test_auth_layout_renders_status_as_a_toast(): void
    {
        $this
            ->withSession([
                'status' => 'Tautan reset password sudah dikirim.',
            ])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('data-laras-toast-region', false)
            ->assertSee('Tautan reset password sudah dikirim.')
            ->assertDontSee(
                'border-emerald-200 bg-emerald-50',
                false
            );
    }

    public function test_account_archive_uses_laras_confirmation_dialog(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'type' => AccountType::Bank,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('data-confirm', false)
            ->assertSee('Arsipkan rekening?', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_blade_views_do_not_use_native_javascript_confirm(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                resource_path('views')
            )
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (
                ! $file->isFile()
                || $file->getExtension() !== 'php'
            ) {
                continue;
            }

            $contents = file_get_contents(
                $file->getPathname()
            );

            $this->assertIsString($contents);
            $this->assertStringNotContainsString(
                'confirm(',
                $contents,
                "Native confirm masih ditemukan pada {$file->getPathname()}"
            );
        }
    }

    public function test_toast_shell_uses_valid_relative_positioning_class(): void
    {
        $script = file_get_contents(
            resource_path('js/ui/toast.js')
        );

        $this->assertIsString($script);

        $this->assertStringContainsString(
            'pointer-events-auto relative overflow-hidden',
            $script
        );

        $this->assertStringNotContainsString(
            '<relativ>',
            $script
        );
    }

    private function completedUser(): User
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        return $user;
    }
}
