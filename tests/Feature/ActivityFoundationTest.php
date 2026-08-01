<?php

namespace Tests\Feature;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\ActivityRecommendationService;
use App\Services\ActivityService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_has_expected_relationships_and_casts(): void
    {
        $user = $this->userWithPreference();

        $activity = Activity::factory()
            ->deadline()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Kumpulkan tugas',
            ]);

        $this->assertTrue(
            $activity->user->is($user)
        );

        $this->assertSame(
            ActivityType::Deadline,
            $activity->type
        );

        $this->assertSame(
            ActivityPriority::Urgent,
            $activity->priority
        );

        $this->assertSame(
            ActivityStatus::Planned,
            $activity->status
        );

        $this->assertNotNull(
            $activity->due_at
        );

        $this->assertTrue(
            $activity->is_flexible
        );

        $this->assertCount(
            1,
            $user->activities
        );
    }

    public function test_service_can_create_scheduled_event(): void
    {
        $user = $this->userWithPreference();

        $activity = app(
            ActivityService::class
        )->create(
            user: $user,
            data: [
                'title' => 'Pertemuan kelompok',
                'description' =>
                    'Membahas pembagian tugas proyek.',
                'type' => 'event',
                'priority' => 'high',
                'starts_at' => '2026-08-03 10:00',
                'ends_at' => '2026-08-03 11:30',
                'due_at' => null,
                'all_day' => false,
                'estimated_minutes' => 90,
                'is_flexible' => false,
                'location' => 'Perpustakaan',
                'color' => '#8B5CF6',
            ]
        );

        $this->assertSame(
            ActivityType::Event,
            $activity->type
        );

        $this->assertSame(
            ActivityPriority::High,
            $activity->priority
        );

        $this->assertSame(
            ActivityStatus::Planned,
            $activity->status
        );

        $this->assertSame(
            'Pertemuan kelompok',
            $activity->title
        );

        $this->assertSame(
            90,
            $activity->estimated_minutes
        );

        $this->assertFalse(
            $activity->is_flexible
        );

        $this->assertDatabaseHas(
            'activities',
            [
                'id' => $activity->id,
                'user_id' => $user->id,
                'type' => 'event',
                'priority' => 'high',
                'status' => 'planned',
            ]
        );
    }

    public function test_deadline_requires_due_date(): void
    {
        $user = $this->userWithPreference();

        $this->expectException(
            DomainException::class
        );

        $this->expectExceptionMessage(
            'Aktivitas deadline wajib memiliki tenggat.'
        );

        app(ActivityService::class)->create(
            user: $user,
            data: [
                'title' => 'Kumpulkan laporan',
                'type' => 'deadline',
                'priority' => 'urgent',
                'due_at' => null,
            ]
        );
    }

    public function test_end_time_cannot_precede_start_time(): void
    {
        $user = $this->userWithPreference();

        try {
            app(ActivityService::class)->create(
                user: $user,
                data: [
                    'title' => 'Jadwal tidak valid',
                    'type' => 'event',
                    'priority' => 'medium',
                    'starts_at' => '2026-08-03 12:00',
                    'ends_at' => '2026-08-03 10:00',
                ]
            );

            $this->fail(
                'DomainException seharusnya dilempar.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'Waktu selesai tidak boleh mendahului waktu mulai.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'activities',
            0
        );
    }

    public function test_activity_can_be_started_completed_and_reopened(): void
    {
        $user = $this->userWithPreference();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'status' => ActivityStatus::Planned,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $service = app(
            ActivityService::class
        );

        $started = $service->start(
            $user,
            $activity->id
        );

        $this->assertSame(
            ActivityStatus::InProgress,
            $started->status
        );

        $completed = $service->complete(
            $user,
            $activity->id
        );

        $this->assertSame(
            ActivityStatus::Completed,
            $completed->status
        );

        $this->assertNotNull(
            $completed->completed_at
        );

        $reopened = $service->reopen(
            $user,
            $activity->id
        );

        $this->assertSame(
            ActivityStatus::Planned,
            $reopened->status
        );

        $this->assertNull(
            $reopened->completed_at
        );

        $this->assertNull(
            $reopened->cancelled_at
        );
    }

    public function test_cancelled_activity_cannot_be_completed_directly(): void
    {
        $user = $this->userWithPreference();

        $activity = Activity::factory()
            ->cancelled()
            ->create([
                'user_id' => $user->id,
            ]);

        $this->expectException(
            DomainException::class
        );

        $this->expectExceptionMessage(
            'Aktivitas yang dibatalkan harus dibuka kembali sebelum diselesaikan.'
        );

        app(ActivityService::class)->complete(
            $user,
            $activity->id
        );
    }

    public function test_user_cannot_modify_another_users_activity(): void
    {
        $user = $this->userWithPreference();
        $otherUser = $this->userWithPreference();

        $activity = Activity::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->expectException(
            ModelNotFoundException::class
        );

        app(ActivityService::class)->complete(
            $user,
            $activity->id
        );
    }

    public function test_archived_activity_is_excluded_from_normal_queries(): void
    {
        $user = $this->userWithPreference();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        app(ActivityService::class)->archive(
            $user,
            $activity->id
        );

        $this->assertSoftDeleted(
            'activities',
            [
                'id' => $activity->id,
            ]
        );

        $this->assertNull(
            Activity::query()->find(
                $activity->id
            )
        );

        $this->assertNotNull(
            Activity::withTrashed()->find(
                $activity->id
            )
        );
    }

    public function test_recommendation_service_prioritizes_urgent_overdue_activity(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 12:00:00',
            'Asia/Jakarta'
        );

        $user = $this->userWithPreference();

        $lowPriority = Activity::factory()->create([
            'user_id' => $user->id,
            'title' => 'Merapikan catatan',
            'priority' => ActivityPriority::Low,
            'status' => ActivityStatus::Planned,
            'due_at' => $reference->addDays(10),
            'estimated_minutes' => 60,
            'is_flexible' => true,
        ]);

        $urgentOverdue = Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Kumpulkan tugas',
                'status' => ActivityStatus::Planned,
                'due_at' => $reference->subHour(),
                'estimated_minutes' => 30,
                'is_flexible' => false,
            ]);

        $completed = Activity::factory()
            ->completed()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Aktivitas selesai',
                'due_at' => $reference->subDay(),
            ]);

        $recommendations = app(
            ActivityRecommendationService::class
        )->rank(
            user: $user,
            reference: $reference,
            limit: 5
        );

        $this->assertCount(
            2,
            $recommendations
        );

        $this->assertTrue(
            $recommendations
                ->first()['activity']
                ->is($urgentOverdue)
        );

        $this->assertStringContainsString(
            'melewati tenggat',
            $recommendations
                ->first()['reason']
        );

        $this->assertFalse(
            $recommendations
                ->pluck('activity')
                ->contains(
                    fn (Activity $activity): bool =>
                        $activity->is($completed)
                )
        );

        $this->assertTrue(
            $recommendations
                ->last()['activity']
                ->is($lowPriority)
        );
    }

    private function userWithPreference(): User
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
