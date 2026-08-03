<?php

namespace App\Services;

use App\Enums\DataDeletionScope;
use App\Enums\RecommendationInteractionType;
use App\Models\User;

class DataDeletionPreviewService
{
    public function __construct(
        private readonly DataDeletionScopeService $scopes
    ) {}

    /**
     * @param  list<string>  $notificationIds
     * @return array{
     *     resource: string,
     *     scope: string,
     *     count: int,
     *     details: array{read: int, unread: int},
     *     is_empty: bool,
     *     message: string
     * }
     */
    public function notifications(
        User $user,
        DataDeletionScope $scope,
        array $notificationIds = [],
        ?int $olderThanDays = null
    ): array {
        $query = $this->scopes->notifications(
            user: $user,
            scope: $scope,
            notificationIds: $notificationIds,
            olderThanDays: $olderThanDays
        );

        $count = (clone $query)->count();

        $readCount = (clone $query)
            ->whereNotNull('read_at')
            ->count();

        $unreadCount = $count - $readCount;

        return [
            'resource' => 'notifications',
            'scope' => $scope->value,
            'count' => $count,
            'details' => [
                'read' => $readCount,
                'unread' => $unreadCount,
            ],
            'is_empty' => $count === 0,
            'message' => $count === 0
                ? 'Tidak ada notifikasi yang akan dihapus.'
                : $count.' notifikasi akan dihapus permanen.',
        ];
    }

    /**
     * @param  list<int>  $interactionIds
     * @return array{
     *     resource: string,
     *     scope: string,
     *     count: int,
     *     details: array<string, int>,
     *     is_empty: bool,
     *     message: string
     * }
     */
    public function recommendationInteractions(
        User $user,
        DataDeletionScope $scope,
        array $interactionIds = [],
        ?int $olderThanDays = null
    ): array {
        $query = $this->scopes
            ->recommendationInteractions(
                user: $user,
                scope: $scope,
                interactionIds: $interactionIds,
                olderThanDays: $olderThanDays
            );

        $count = (clone $query)->count();

        $details = [];

        foreach (
            RecommendationInteractionType::cases() as $type
        ) {
            $details[$type->value] = (clone $query)
                ->where(
                    'interaction_type',
                    $type->value
                )
                ->count();
        }

        return [
            'resource' => 'recommendation_interactions',
            'scope' => $scope->value,
            'count' => $count,
            'details' => $details,
            'is_empty' => $count === 0,
            'message' => $count === 0
                ? 'Tidak ada riwayat rekomendasi yang akan dihapus.'
                : $count.' riwayat rekomendasi akan dihapus permanen.',
        ];
    }
}
