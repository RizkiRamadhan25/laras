<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModernFormFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_floating_fields_and_password_toggle(): void
    {
        $response = $this
            ->get(route('login'))
            ->assertOk()
            ->assertSee(
                'data-laras-field',
                false
            )
            ->assertSee(
                'data-laras-password-field',
                false
            )
            ->assertSee(
                'data-laras-password-toggle',
                false
            )
            ->assertSee(
                'Tampilkan kata sandi'
            );

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $response->getContent(),
                'data-laras-field'
            )
        );
    }

    public function test_password_reset_form_uses_modern_password_fields(): void
    {
        $response = $this
            ->get(
                route('password.reset', [
                    'token' => 'reset-token',
                    'email' => 'rizki@example.com',
                ])
            )
            ->assertOk()
            ->assertSee(
                'data-laras-password-toggle',
                false
            );

        $this->assertSame(
            2,
            substr_count(
                $response->getContent(),
                'data-laras-password-field'
            )
        );
    }

    public function test_settings_security_and_privacy_use_password_toggles(): void
    {
        $user = $this->completedUser();

        $response = $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'data-laras-password-toggle',
                false
            )
            ->assertSee(
                'Ketik HAPUS AKUN'
            );

        $this->assertGreaterThanOrEqual(
            6,
            substr_count(
                $response->getContent(),
                'data-laras-password-field'
            )
        );
    }

    public function test_form_control_script_and_styles_are_registered(): void
    {
        $appJs = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertIsString($appJs);
        $this->assertStringContainsString(
            "import '../css/ui/forms.css';",
            $appJs
        );
        $this->assertStringContainsString(
            "import './ui/form-controls';",
            $appJs
        );

        $this->assertFileExists(
            resource_path('js/ui/form-controls.js')
        );

        $this->assertFileExists(
            resource_path('css/ui/forms.css')
        );
    }

    public function test_reusable_modern_form_components_are_available(): void
    {
        $components = [
            'floating-input.blade.php',
            'floating-select.blade.php',
            'floating-textarea.blade.php',
            'password-input.blade.php',
        ];

        foreach ($components as $component) {
            $this->assertFileExists(
                resource_path(
                    'views/components/ui/'.$component
                )
            );
        }
    }

    private function completedUser(): User
    {
        $user = User::factory()->create([
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
