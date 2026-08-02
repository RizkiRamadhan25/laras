<?php

namespace Database\Factories;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => null,

            'type' => ActivityType::Task,
            'priority' => ActivityPriority::Medium,
            'status' => ActivityStatus::Planned,

            'starts_at' => null,
            'ends_at' => null,
            'due_at' => fake()->dateTimeBetween(
                'now',
                '+7 days'
            ),

            'all_day' => false,
            'estimated_minutes' => 60,
            'is_flexible' => true,

            'location' => null,
            'color' => '#3B82F6',
            'sort_order' => 0,

            'completed_at' => null,
            'cancelled_at' => null,
            'metadata' => null,
        ];
    }

    public function event(): static
    {
        return $this->state(function (): array {
            $startsAt = now()->addDay()->startOfHour();

            return [
                'type' => ActivityType::Event,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHour(),
                'due_at' => null,
                'is_flexible' => false,
            ];
        });
    }

    public function deadline(): static
    {
        return $this->state(fn (): array => [
            'type' => ActivityType::Deadline,
            'starts_at' => null,
            'ends_at' => null,
            'due_at' => now()->addDays(3),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => ActivityStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ActivityStatus::Completed,
            'completed_at' => now(),
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => ActivityStatus::Cancelled,
            'completed_at' => null,
            'cancelled_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (): array => [
            'priority' => ActivityPriority::Urgent,
        ]);
    }
}
