<?php

namespace Tests\Feature;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_user_can_view_activity_pages(): void
    {
        $user = $this->completedUser();

        Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Menyelesaikan laporan',
        ]);

        $this
            ->actingAs($user)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Aktivitas')
            ->assertSee('Menyelesaikan laporan');

        $this
            ->actingAs($user)
            ->get(route('priorities.index'))
            ->assertOk()
            ->assertSee('Prioritas');

        $this
            ->actingAs($user)
            ->get(route('activities.create'))
            ->assertOk()
            ->assertSee('Tambah aktivitas baru');
    }

    public function test_user_can_create_task(): void
    {
        $user = $this->completedUser();

        $response = $this
            ->actingAs($user)
            ->post(route('activities.store'), [
                'title' => 'Mengerjakan dokumentasi',
                'description' =>
                    'Menyelesaikan dokumentasi aplikasi Laras.',
                'type' => 'task',
                'priority' => 'high',
                'starts_at' => '2026-08-02T09:00',
                'ends_at' => '2026-08-02T11:00',
                'due_at' => '2026-08-03T18:00',
                'all_day' => '0',
                'estimated_minutes' => '120',
                'is_flexible' => '1',
                'location' => 'Rumah',
                'color' => '#F59E0B',
            ]);

        $activity = Activity::query()
            ->firstOrFail();

        $response
            ->assertRedirectToRoute(
                'activities.edit',
                $activity->id
            )
            ->assertSessionHas('status');

        $this->assertDatabaseHas(
            'activities',
            [
                'user_id' => $user->id,
                'title' => 'Mengerjakan dokumentasi',
                'type' => 'task',
                'priority' => 'high',
                'status' => 'planned',
                'estimated_minutes' => 120,
                'is_flexible' => true,
            ]
        );
    }

    public function test_event_requires_start_time(): void
    {
        $user = $this->completedUser();

        $response = $this
            ->actingAs($user)
            ->from(route('activities.create'))
            ->post(route('activities.store'), [
                'title' => 'Pertemuan kelompok',
                'type' => 'event',
                'priority' => 'medium',
                'starts_at' => null,
                'ends_at' => null,
                'due_at' => null,
                'all_day' => '0',
                'estimated_minutes' => '60',
                'is_flexible' => '0',
                'location' => null,
                'color' => '#3B82F6',
            ]);

        $response
            ->assertRedirectToRoute(
                'activities.create'
            )
            ->assertSessionHasErrors(
                'starts_at'
            );

        $this->assertDatabaseCount(
            'activities',
            0
        );
    }

    public function test_user_can_update_own_activity(): void
    {
        $user = $this->completedUser();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Judul lama',
            'status' => ActivityStatus::Planned,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'activities.update',
                    $activity->id
                ),
                [
                    'title' => 'Judul baru',
                    'description' => null,
                    'type' => 'deadline',
                    'priority' => 'urgent',
                    'starts_at' => null,
                    'ends_at' => null,
                    'due_at' => '2026-08-05T23:00',
                    'all_day' => '0',
                    'estimated_minutes' => '90',
                    'is_flexible' => '0',
                    'location' => null,
                    'color' => '#EF4444',
                ]
            );

        $response
            ->assertRedirectToRoute(
                'activities.edit',
                $activity->id
            )
            ->assertSessionHas('status');

        $activity->refresh();

        $this->assertSame(
            'Judul baru',
            $activity->title
        );

        $this->assertSame(
            ActivityType::Deadline,
            $activity->type
        );

        $this->assertSame(
            ActivityPriority::Urgent,
            $activity->priority
        );
    }

    public function test_user_cannot_edit_another_users_activity(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $activity = Activity::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'activities.edit',
                    $activity->id
                )
            )
            ->assertNotFound();
    }

    public function test_activity_status_can_be_changed_from_interface(): void
    {
        $user = $this->completedUser();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'status' => ActivityStatus::Planned,
        ]);

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'activities.start',
                    $activity->id
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(
            ActivityStatus::InProgress,
            $activity->fresh()->status
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'activities.complete',
                    $activity->id
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(
            ActivityStatus::Completed,
            $activity->fresh()->status
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'activities.reopen',
                    $activity->id
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(
            ActivityStatus::Planned,
            $activity->fresh()->status
        );
    }

    public function test_activity_can_be_cancelled_and_reopened(): void
    {
        $user = $this->completedUser();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'activities.cancel',
                    $activity->id
                )
            )
            ->assertRedirect();

        $this->assertSame(
            ActivityStatus::Cancelled,
            $activity->fresh()->status
        );

        $this->assertNotNull(
            $activity->fresh()->cancelled_at
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'activities.reopen',
                    $activity->id
                )
            )
            ->assertRedirect();

        $this->assertSame(
            ActivityStatus::Planned,
            $activity->fresh()->status
        );
    }

    public function test_activity_can_be_archived_and_restored(): void
    {
        $user = $this->completedUser();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'activities.destroy',
                    $activity->id
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSoftDeleted(
            'activities',
            [
                'id' => $activity->id,
            ]
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'activities.restore',
                    $activity->id
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas(
            'activities',
            [
                'id' => $activity->id,
                'status' => 'planned',
                'deleted_at' => null,
            ]
        );
    }

    public function test_activity_list_can_be_filtered(): void
    {
        $user = $this->completedUser();

        Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Tugas penting',
            'type' => ActivityType::Task,
            'priority' => ActivityPriority::Urgent,
            'status' => ActivityStatus::Planned,
        ]);

        Activity::factory()->event()->create([
            'user_id' => $user->id,
            'title' => 'Acara santai',
            'priority' => ActivityPriority::Low,
            'status' => ActivityStatus::Planned,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'activities.index',
                    [
                        'view' => 'open',
                        'type' => 'task',
                        'priority' => 'urgent',
                        'search' => 'penting',
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('Tugas penting')
            ->assertDontSee('Acara santai');
    }

    public function test_priority_page_orders_urgent_activity_first(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 12:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->completedUser();

        Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Prioritas rendah',
            'priority' => ActivityPriority::Low,
            'due_at' => $reference->addWeek(),
        ]);

        Activity::factory()->urgent()->create([
            'user_id' => $user->id,
            'title' => 'Prioritas mendesak',
            'due_at' => $reference->addHour(),
            'is_flexible' => false,
        ]);

        $this
            ->actingAs($user)
            ->get(route('priorities.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Prioritas mendesak',
                'Prioritas rendah',
            ])
            ->assertSee(
                'Urutan fokus yang disarankan'
            );
    }

    public function test_today_view_only_shows_todays_activities(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 08:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->completedUser();

        Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Agenda hari ini',
            'starts_at' =>
                $reference->addHours(2),
            'due_at' => null,
        ]);

        Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Agenda besok',
            'starts_at' =>
                $reference->addDay(),
            'due_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'activities.index',
                    ['view' => 'today']
                )
            )
            ->assertOk()
            ->assertSee('Agenda hari ini')
            ->assertDontSee('Agenda besok');
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
