<?php

namespace Tests\Feature;

use App\Enums\ActivityPriority;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModernFormMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_and_preference_forms_use_modern_fields(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('data-modern-profile-form', false)
            ->assertSee('data-modern-preferences-form', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="timezone"', false)
            ->assertSee('laras-field__input', false)
            ->assertSee('laras-field__select', false);
    }

    public function test_activity_create_form_uses_modern_fields_and_choice_cards(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('activities.create'))
            ->assertOk()
            ->assertSee('data-modern-activity-form', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="estimated_minutes"', false)
            ->assertSee('laras-field__textarea', false)
            ->assertSee('laras-choice-card', false);
    }

    public function test_activity_edit_form_keeps_existing_values(): void
    {
        $user = $this->completedUser();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Rapat proyek Laras',
            'location' => 'Google Meet',
            'type' => ActivityType::Event,
            'priority' => ActivityPriority::High,
        ]);

        $this
            ->actingAs($user)
            ->get(route('activities.edit', $activity))
            ->assertOk()
            ->assertSee('value="Rapat proyek Laras"', false)
            ->assertSee('value="Google Meet"', false)
            ->assertSee('value="event"', false)
            ->assertSee('value="high"', false);
    }

    public function test_activity_filter_uses_compact_modern_fields_and_preserves_values(): void
    {
        $user = $this->completedUser();

        Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Tugas penting',
            'type' => ActivityType::Task,
            'priority' => ActivityPriority::Urgent,
        ]);

        $this
            ->actingAs($user)
            ->get(route('activities.index', [
                'view' => 'open',
                'search' => 'penting',
                'type' => 'task',
                'priority' => 'urgent',
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('data-activity-filter-form', false)
            ->assertSee('data-density="compact"', false)
            ->assertSee('value="penting"', false)
            ->assertSee('value="2026-08-01"', false)
            ->assertSee('value="2026-08-31"', false)
            ->assertSee('Terapkan filter');
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
