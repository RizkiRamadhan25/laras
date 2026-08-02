<?php

namespace App\Services;

use App\Enums\RecommendationInteractionType;
use App\Models\RecommendationInteraction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RecommendationInteractionService
{
    /**
     * @param  array<string, mixed>  $item
     */
    public function record(
        User $user,
        array $item,
        RecommendationInteractionType $type
    ): RecommendationInteraction {
        return RecommendationInteraction::query()
            ->create([
                'user_id' => $user->id,

                'recommendation_key' => $item['key'],

                'recommendation_kind' => $item['kind'],

                'interaction_type' => $type,

                'title' => mb_substr(
                    $item['title'],
                    0,
                    200
                ),

                'snapshot' => [
                    'message' => $item['message'] ?? null,

                    'meta' => $item['meta'] ?? null,

                    'severity' => $item['severity'] ?? null,

                    'score' => $item['score'] ?? null,

                    'icon' => $item['icon'] ?? null,

                    'action_url' => $item['action_url'] ?? null,

                    'action_label' => $item['action_label'] ?? null,
                ],

                'occurred_at' => now(),
            ])
            ->refresh();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array{
     *     items: Collection<int, array<string, mixed>>,
     *     suppressed_count: int
     * }
     */
    public function applyFeedback(
        User $user,
        Collection $items,
        CarbonImmutable $reference
    ): array {
        if ($items->isEmpty()) {
            return [
                'items' => $items,
                'suppressed_count' => 0,
            ];
        }

        $keys = $items
            ->pluck('key')
            ->filter()
            ->values()
            ->all();

        $latestInteractions =
            RecommendationInteraction::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->whereIn(
                    'recommendation_key',
                    $keys
                )
                ->orderByDesc(
                    'occurred_at'
                )
                ->orderByDesc('id')
                ->get()
                ->unique(
                    'recommendation_key'
                )
                ->keyBy(
                    'recommendation_key'
                );

        $decorated = $items->map(
            function (
                array $item
            ) use (
                $latestInteractions,
                $reference
            ): array {
                $interaction =
                    $latestInteractions->get(
                        $item['key']
                    );

                if (
                    ! $interaction instanceof RecommendationInteraction
                ) {
                    $item['latest_interaction'] =
                        null;

                    $item['_suppressed'] =
                        false;

                    return $item;
                }

                $type =
                    $interaction
                        ->interaction_type;

                $item['latest_interaction'] = [
                    'type' => $type->value,
                    'label' => $type->label(),

                    'occurred_at' => $interaction
                        ->occurred_at,
                ];

                if (
                    ! $type
                        ->suppressesRecommendation()
                ) {
                    $item['_suppressed'] =
                        false;

                    return $item;
                }

                $suppressedUntil =
                    $interaction
                        ->occurred_at
                        ->addHours(
                            $type
                                ->suppressionHours()
                        );

                $item['_suppressed'] =
                    $suppressedUntil
                        ->isAfter($reference);

                return $item;
            }
        );

        $suppressedCount = $decorated
            ->where(
                '_suppressed',
                true
            )
            ->count();

        $visibleItems = $decorated
            ->reject(
                fn (array $item): bool => $item['_suppressed']
            )
            ->map(
                fn (array $item): array => Arr::except(
                    $item,
                    '_suppressed'
                )
            )
            ->values();

        return [
            'items' => $visibleItems,

            'suppressed_count' => $suppressedCount,
        ];
    }
}
