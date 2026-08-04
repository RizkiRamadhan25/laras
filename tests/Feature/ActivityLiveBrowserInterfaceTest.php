<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Models\Activity;
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
            ->assertSee('data-activity-summary', false)
            ->assertSee('data-activity-summary-value="open"', false)
            ->assertSee('data-activity-summary-value="today"', false)
            ->assertSee('data-activity-summary-value="overdue"', false)
            ->assertSee('data-activity-summary-value="completed_month"', false)
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

    public function test_activity_cards_expose_async_action_hooks(): void
    {
        $user = $this->completedUser();

        $plannedActivity = Activity::factory()->create([
            'user_id' => $user->id,
            'status' => ActivityStatus::Planned,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee(
                'data-activity-list',
                false
            )
            ->assertSee(
                'data-activity-card',
                false
            )
            ->assertSee(
                'data-activity-id="'.
                    $plannedActivity->id.
                    '"',
                false
            )
            ->assertSee(
                'data-activity-action-form',
                false
            )
            ->assertSee(
                'data-activity-action="start"',
                false
            )
            ->assertSee(
                'data-activity-action="complete"',
                false
            )
            ->assertSee(
                'data-activity-action="cancel"',
                false
            )
            ->assertSee(
                'data-activity-action="archive"',
                false
            )
            ->assertSee(
                'data-activity-action-button',
                false
            );

        Activity::factory()->create([
            'user_id' => $user->id,
            'status' => ActivityStatus::Completed,
            'completed_at' => now(),
            'cancelled_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'activities.index',
                    ['view' => 'completed']
                )
            )
            ->assertOk()
            ->assertSee(
                'data-activity-action="reopen"',
                false
            );

        $archivedActivity =
            Activity::factory()->create([
                'user_id' => $user->id,
                'status' => ActivityStatus::Cancelled,
                'completed_at' => null,
                'cancelled_at' => now(),
            ]);

        $archivedActivity->delete();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'activities.index',
                    ['view' => 'archived']
                )
            )
            ->assertOk()
            ->assertSee(
                'data-activity-action="restore"',
                false
            );
    }

    public function test_activity_action_javascript_supports_async_requests(): void
    {
        $appScript = file_get_contents(
            resource_path('js/app.js')
        );

        $actionScript = file_get_contents(
            resource_path(
                'js/features/activity-actions.js'
            )
        );

        $browserScript = file_get_contents(
            resource_path(
                'js/features/activity-browser.js'
            )
        );

        $this->assertIsString($appScript);
        $this->assertIsString($actionScript);
        $this->assertIsString($browserScript);

        $this->assertStringContainsString(
            "import './features/activity-actions';",
            $appScript
        );

        $this->assertStringContainsString(
            'export async function loadActivityBrowser',
            $browserScript
        );

        $this->assertStringContainsString(
            '[data-activity-action-form]',
            $actionScript
        );

        $this->assertStringContainsString(
            "Accept: 'application/json'",
            $actionScript
        );

        $this->assertStringContainsString(
            "'X-Requested-With':",
            $actionScript
        );

        $this->assertStringContainsString(
            'new FormData(form)',
            $actionScript
        );

        $this->assertStringContainsString(
            'form.dataset.confirmBypass',
            $actionScript
        );

        $this->assertStringContainsString(
            'loadActivityBrowser(',
            $actionScript
        );

        $this->assertStringContainsString(
            'window.LarasToast?.success',
            $actionScript
        );

        $this->assertStringContainsString(
            'window.LarasToast?.error',
            $actionScript
        );
    }

    public function test_activity_browser_refreshes_summary_and_list_together(): void
    {
        $browserScript = file_get_contents(
            resource_path(
                'js/features/activity-browser.js'
            )
        );

        $actionScript = file_get_contents(
            resource_path(
                'js/features/activity-actions.js'
            )
        );

        $this->assertIsString(
            $browserScript
        );

        $this->assertIsString(
            $actionScript
        );

        $this->assertStringContainsString(
            "const SUMMARY_SELECTOR = '[data-activity-summary]'",
            $browserScript
        );

        $this->assertStringContainsString(
            'documentResult.querySelector(',
            $browserScript
        );

        $this->assertStringContainsString(
            'replacementSummary',
            $browserScript
        );

        $this->assertStringContainsString(
            'summary.replaceWith(',
            $browserScript
        );

        $this->assertStringContainsString(
            'browser.replaceWith(',
            $browserScript
        );

        $this->assertStringContainsString(
            'return true;',
            $browserScript
        );

        $this->assertStringContainsString(
            'showErrorToast: false',
            $actionScript
        );

        $this->assertStringContainsString(
            'if (! refreshed)',
            $actionScript
        );

        $this->assertStringContainsString(
            'window.LarasToast?.warning',
            $actionScript
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
