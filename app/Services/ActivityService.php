<?php

namespace App\Services;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use ValueError;

class ActivityService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        User $user,
        array $data
    ): Activity {
        $payload = $this->normalizePayload(
            user: $user,
            data: $data
        );

        return DB::transaction(
            fn (): Activity => $user
                ->activities()
                ->create([
                    ...$payload,
                    'status' => ActivityStatus::Planned,
                    'completed_at' => null,
                    'cancelled_at' => null,
                ]),
            3
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        User $user,
        int $activityId,
        array $data
    ): Activity {
        return DB::transaction(function () use (
            $user,
            $activityId,
            $data
        ): Activity {
            $activity = $this->lockOwnedActivity(
                $user,
                $activityId
            );

            if (
                $activity->status
                === ActivityStatus::Completed
            ) {
                throw new DomainException(
                    'Aktivitas yang sudah selesai harus dibuka kembali sebelum diedit.'
                );
            }

            if (
                $activity->status
                === ActivityStatus::Cancelled
            ) {
                throw new DomainException(
                    'Aktivitas yang dibatalkan harus dibuka kembali sebelum diedit.'
                );
            }

            $payload = $this->normalizePayload(
                user: $user,
                data: $data
            );

            $activity->update($payload);

            return $activity->refresh();
        }, 3);
    }

    public function start(
        User $user,
        int $activityId
    ): Activity {
        return DB::transaction(function () use (
            $user,
            $activityId
        ): Activity {
            $activity = $this->lockOwnedActivity(
                $user,
                $activityId
            );

            if (
                $activity->status
                === ActivityStatus::Completed
            ) {
                throw new DomainException(
                    'Aktivitas yang sudah selesai tidak dapat dimulai kembali secara langsung.'
                );
            }

            if (
                $activity->status
                === ActivityStatus::Cancelled
            ) {
                throw new DomainException(
                    'Aktivitas yang dibatalkan harus dibuka kembali terlebih dahulu.'
                );
            }

            $activity->forceFill([
                'status' => ActivityStatus::InProgress,
                'completed_at' => null,
                'cancelled_at' => null,
            ])->save();

            return $activity->refresh();
        }, 3);
    }

    public function complete(
        User $user,
        int $activityId
    ): Activity {
        return DB::transaction(function () use (
            $user,
            $activityId
        ): Activity {
            $activity = $this->lockOwnedActivity(
                $user,
                $activityId
            );

            if (
                $activity->status
                === ActivityStatus::Cancelled
            ) {
                throw new DomainException(
                    'Aktivitas yang dibatalkan harus dibuka kembali sebelum diselesaikan.'
                );
            }

            if (
                $activity->status
                === ActivityStatus::Completed
            ) {
                return $activity;
            }

            $activity->forceFill([
                'status' => ActivityStatus::Completed,
                'completed_at' => now(),
                'cancelled_at' => null,
            ])->save();

            return $activity->refresh();
        }, 3);
    }

    public function cancel(
        User $user,
        int $activityId
    ): Activity {
        return DB::transaction(function () use (
            $user,
            $activityId
        ): Activity {
            $activity = $this->lockOwnedActivity(
                $user,
                $activityId
            );

            if (
                $activity->status
                === ActivityStatus::Completed
            ) {
                throw new DomainException(
                    'Aktivitas yang sudah selesai tidak dapat dibatalkan.'
                );
            }

            if (
                $activity->status
                === ActivityStatus::Cancelled
            ) {
                return $activity;
            }

            $activity->forceFill([
                'status' => ActivityStatus::Cancelled,
                'completed_at' => null,
                'cancelled_at' => now(),
            ])->save();

            return $activity->refresh();
        }, 3);
    }

    public function reopen(
        User $user,
        int $activityId
    ): Activity {
        return DB::transaction(function () use (
            $user,
            $activityId
        ): Activity {
            $activity = $this->lockOwnedActivity(
                $user,
                $activityId
            );

            if ($activity->status->isOpen()) {
                return $activity;
            }

            $activity->forceFill([
                'status' => ActivityStatus::Planned,
                'completed_at' => null,
                'cancelled_at' => null,
            ])->save();

            return $activity->refresh();
        }, 3);
    }

    public function archive(
        User $user,
        int $activityId
    ): void {
        DB::transaction(function () use (
            $user,
            $activityId
        ): void {
            $activity = $this->lockOwnedActivity(
                $user,
                $activityId
            );

            $activity->delete();
        }, 3);
    }

    public function restore(
        User $user,
        int $activityId
    ): Activity {
        return DB::transaction(function () use (
            $user,
            $activityId
        ): Activity {
            $activity = Activity::query()
                ->onlyTrashed()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->findOrFail($activityId);

            $activity->restore();

            $activity->forceFill([
                'status' => ActivityStatus::Planned,
                'completed_at' => null,
                'cancelled_at' => null,
            ])->save();

            return $activity->refresh();
        }, 3);
    }

    private function lockOwnedActivity(
        User $user,
        int $activityId
    ): Activity {
        return Activity::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->findOrFail($activityId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(
        User $user,
        array $data
    ): array {
        $title = trim(
            (string) ($data['title'] ?? '')
        );

        if ($title === '') {
            throw new DomainException(
                'Judul aktivitas wajib diisi.'
            );
        }

        if (mb_strlen($title) > 160) {
            throw new DomainException(
                'Judul aktivitas maksimal 160 karakter.'
            );
        }

        $type = $this->activityType(
            $data['type'] ?? ActivityType::Task
        );

        $priority = $this->activityPriority(
            $data['priority']
                ?? ActivityPriority::Medium
        );

        $startsAt = $this->normalizeDateTime(
            $user,
            $data['starts_at'] ?? null
        );

        $endsAt = $this->normalizeDateTime(
            $user,
            $data['ends_at'] ?? null
        );

        $dueAt = $this->normalizeDateTime(
            $user,
            $data['due_at'] ?? null
        );

        $this->assertTemporalRules(
            type: $type,
            startsAt: $startsAt,
            endsAt: $endsAt,
            dueAt: $dueAt
        );

        $estimatedMinutes = $this->estimatedMinutes(
            $data['estimated_minutes'] ?? null
        );

        $color = $this->normalizeColor(
            $data['color'] ?? $priority->color()
        );

        return [
            'title' => $title,

            'description' => $this->nullableString(
                $data['description'] ?? null
            ),

            'type' => $type,
            'priority' => $priority,

            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'due_at' => $dueAt,

            'all_day' => (bool) (
                $data['all_day'] ?? false
            ),

            'estimated_minutes' => $estimatedMinutes,

            'is_flexible' => (bool) (
                $data['is_flexible'] ?? true
            ),

            'location' => $this->nullableString(
                $data['location'] ?? null
            ),

            'color' => $color,

            'sort_order' => max(
                0,
                (int) ($data['sort_order'] ?? 0)
            ),

            'metadata' => is_array(
                $data['metadata'] ?? null
            )
                ? $data['metadata']
                : null,
        ];
    }

    private function activityType(
        mixed $value
    ): ActivityType {
        if ($value instanceof ActivityType) {
            return $value;
        }

        try {
            return ActivityType::from(
                (string) $value
            );
        } catch (ValueError) {
            throw new DomainException(
                'Jenis aktivitas tidak valid.'
            );
        }
    }

    private function activityPriority(
        mixed $value
    ): ActivityPriority {
        if ($value instanceof ActivityPriority) {
            return $value;
        }

        try {
            return ActivityPriority::from(
                (string) $value
            );
        } catch (ValueError) {
            throw new DomainException(
                'Prioritas aktivitas tidak valid.'
            );
        }
    }

    private function normalizeDateTime(
        User $user,
        mixed $value
    ): ?CarbonImmutable {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)
                ->setTimezone(config('app.timezone'));
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        $userTimezone = $user->preference()
            ->value('timezone')
            ?? config('laras.defaults.timezone');

        return CarbonImmutable::parse(
            $normalized,
            $userTimezone
        )->setTimezone(config('app.timezone'));
    }

    private function assertTemporalRules(
        ActivityType $type,
        ?CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
        ?CarbonImmutable $dueAt
    ): void {
        if (
            $type === ActivityType::Event
            && $startsAt === null
        ) {
            throw new DomainException(
                'Acara wajib memiliki waktu mulai.'
            );
        }

        if (
            $type === ActivityType::Deadline
            && $dueAt === null
        ) {
            throw new DomainException(
                'Aktivitas deadline wajib memiliki tenggat.'
            );
        }

        if (
            $endsAt !== null
            && $startsAt === null
        ) {
            throw new DomainException(
                'Waktu selesai membutuhkan waktu mulai.'
            );
        }

        if (
            $startsAt !== null
            && $endsAt !== null
            && $endsAt->lessThan($startsAt)
        ) {
            throw new DomainException(
                'Waktu selesai tidak boleh mendahului waktu mulai.'
            );
        }

        if (
            $startsAt !== null
            && $dueAt !== null
            && $dueAt->lessThan($startsAt)
        ) {
            throw new DomainException(
                'Tenggat tidak boleh mendahului waktu mulai.'
            );
        }
    }

    private function estimatedMinutes(
        mixed $value
    ): ?int {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $estimatedMinutes = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        if (
            $estimatedMinutes === false
            || $estimatedMinutes < 5
            || $estimatedMinutes > 1440
        ) {
            throw new DomainException(
                'Estimasi durasi harus antara 5 dan 1.440 menit.'
            );
        }

        return $estimatedMinutes;
    }

    private function normalizeColor(
        mixed $value
    ): string {
        $color = strtoupper(
            trim((string) $value)
        );

        if (
            preg_match(
                '/^#[0-9A-F]{6}$/',
                $color
            ) !== 1
        ) {
            throw new DomainException(
                'Format warna aktivitas tidak valid.'
            );
        }

        return $color;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $normalized = trim(
            (string) $value
        );

        return $normalized === ''
            ? null
            : $normalized;
    }
}
