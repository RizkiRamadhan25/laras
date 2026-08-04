<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLiveBrowserInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_datetime_fields_always_float_above_native_controls(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('activities.create'))
            ->assertOk()
            ->assertSee('name="starts_at"', false)
            ->assertSee('name="ends_at"', false)
            ->assertSee('name="due_at"', false)
            ->assertSee('laras-field--always-floating', false);
    }

    public function test_activity_page_exposes_live_browser_controls(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('data-activity-summary',false)
            ->assertSee('data-activity-summary-value="open"',false)
            ->assertSee('data-activity-summary-value="today"',false)
            ->assertSee('data-activity-summary-value="overdue"',false)
            ->assertSee('data-activity-summary-value="completed_month"',false)
            ->assertSee('data-activity-browser', false)
            ->assertSee('data-activity-tab', false)
            ->assertSee('data-activity-filter-form', false)
            ->assertSee('data-activity-search', false)
            ->assertSee('data-activity-reset', false)
            ->assertSee('data-activity-pagination', false);
    }

    public function test_activity_browser_javascript_supports_debounce_and_request_cancellation(): void
    {
        $script = file_get_contents(
            resource_path(
                'js/features/activity-browser.js'
            )
        );

        $this->assertIsString($script);
        $this->assertStringContainsString(
            'AbortController',
            $script
        );
        $this->assertStringContainsString(
            'SEARCH_DEBOUNCE',
            $script
        );
        $this->assertStringContainsString(
            'window.history.replaceState',
            $script
        );
        $this->assertStringContainsString(
            "window.addEventListener('popstate'",
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
