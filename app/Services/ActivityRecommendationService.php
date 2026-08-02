<?php

namespace App\Services;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class ActivityRecommendationService
{
    /**
     * @return Collection<int, array{
     *     activity: Activity,
     *     score: int,
     *     reason: string
     * }>
     */
    public function rank(
        User $user,
        ?DateTimeInterface $reference = null,
        int $limit = 5
    ): Collection {
        $now = $reference !== null
            ? CarbonImmutable::instance($reference)
            : CarbonImmutable::now(
                config('app.timezone')
            );

        return $user->activities()
            ->open()
            ->get()
            ->map(
                fn (Activity $activity): array => $this->scoreActivity(
                    $activity,
                    $now
                )
            )
            ->sort(function (
                array $left,
                array $right
            ): int {
                $scoreComparison =
                    $right['score']
                    <=> $left['score'];

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                $leftTimestamp = $left['activity']
                    ->relevantAt()
                    ?->getTimestamp()
                    ?? PHP_INT_MAX;

                $rightTimestamp = $right['activity']
                    ->relevantAt()
                    ?->getTimestamp()
                    ?? PHP_INT_MAX;

                if ($leftTimestamp !== $rightTimestamp) {
                    return $leftTimestamp
                        <=> $rightTimestamp;
                }

                return $left['activity']->id
                    <=> $right['activity']->id;
            })
            ->take(max(1, $limit))
            ->values();
    }

    /**
     * @return array{
     *     activity: Activity,
     *     score: int,
     *     reason: string
     * }
     */
    private function scoreActivity(
        Activity $activity,
        CarbonImmutable $now
    ): array {
        $score = $activity->priority->weight()
            * 20;

        $reasons = [
            $this->priorityReason(
                $activity->priority
            ),
        ];

        if (
            $activity->status
            === ActivityStatus::InProgress
        ) {
            $score += 18;
            $reasons[] = 'sedang dikerjakan';
        }

        $relevantAt = $activity->relevantAt();

        if ($relevantAt !== null) {
            $secondsUntil =
                $relevantAt->getTimestamp()
                - $now->getTimestamp();

            if ($secondsUntil < 0) {
                $score += 45;
                $reasons[] = 'melewati tenggat';
            } elseif ($secondsUntil <= 86400) {
                $score += 35;
                $reasons[] = 'perlu diselesaikan hari ini';
            } elseif ($secondsUntil <= 259200) {
                $score += 24;
                $reasons[] = 'jatuh tempo dalam tiga hari';
            } elseif ($secondsUntil <= 604800) {
                $score += 12;
                $reasons[] = 'jatuh tempo minggu ini';
            }
        }

        if (! $activity->is_flexible) {
            $score += 8;
            $reasons[] = 'jadwal tidak fleksibel';
        }

        if (
            $activity->estimated_minutes !== null
            && $activity->estimated_minutes <= 30
        ) {
            $score += 5;
            $reasons[] = 'dapat diselesaikan dengan cepat';
        }

        return [
            'activity' => $activity,
            'score' => $score,
            'reason' => ucfirst(
                implode(', ', $reasons)
            ).'.',
        ];
    }

    private function priorityReason(
        ActivityPriority $priority
    ): string {
        return match ($priority) {
            ActivityPriority::Urgent => 'prioritas mendesak',

            ActivityPriority::High => 'prioritas tinggi',

            ActivityPriority::Medium => 'prioritas sedang',

            ActivityPriority::Low => 'prioritas rendah',
        };
    }
}
