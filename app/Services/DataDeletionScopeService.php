<?php

namespace App\Services;

use App\Enums\DataDeletionScope;
use App\Models\RecommendationInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use InvalidArgumentException;

class DataDeletionScopeService
{
    public function __construct(
        private readonly OwnedResourceGuard $ownership
    ) {}

    /**
     * @param  list<string>  $notificationIds
     * @return Builder<DatabaseNotification>
     */
    public function notifications(
        User $user,
        DataDeletionScope $scope,
        array $notificationIds = [],
        ?int $olderThanDays = null
    ): Builder {
        $query = $user->notifications()
            ->getQuery();

        return match ($scope) {
            DataDeletionScope::All => $query,

            DataDeletionScope::Read => $query
                ->whereNotNull('read_at'),

            DataDeletionScope::Selected => $this
                ->selectedNotifications(
                    user: $user,
                    query: $query,
                    notificationIds: $notificationIds
                ),

            DataDeletionScope::Older => $query
                ->where(
                    'created_at',
                    '<',
                    now()->subDays(
                        $this->requiredDays(
                            $olderThanDays
                        )
                    )
                ),
        };
    }

    /**
     * @param  list<int>  $interactionIds
     * @return Builder<RecommendationInteraction>
     */
    public function recommendationInteractions(
        User $user,
        DataDeletionScope $scope,
        array $interactionIds = [],
        ?int $olderThanDays = null
    ): Builder {
        $query = $user
            ->recommendationInteractions()
            ->getQuery();

        return match ($scope) {
            DataDeletionScope::All => $query,

            DataDeletionScope::Selected => $this
                ->selectedRecommendationInteractions(
                    user: $user,
                    query: $query,
                    interactionIds: $interactionIds
                ),

            DataDeletionScope::Older => $query
                ->where(
                    'occurred_at',
                    '<',
                    now()->subDays(
                        $this->requiredDays(
                            $olderThanDays
                        )
                    )
                ),

            DataDeletionScope::Read => throw new InvalidArgumentException(
                'Cakupan read tidak berlaku untuk riwayat rekomendasi.'
            ),
        };
    }

    /**
     * @param  Builder<DatabaseNotification>  $query
     * @param  list<string>  $notificationIds
     * @return Builder<DatabaseNotification>
     */
    private function selectedNotifications(
        User $user,
        Builder $query,
        array $notificationIds
    ): Builder {
        $this->ownership->notifications(
            $user,
            $notificationIds
        );

        return $query->whereKey(
            $notificationIds
        );
    }

    /**
     * @param  Builder<RecommendationInteraction>  $query
     * @param  list<int>  $interactionIds
     * @return Builder<RecommendationInteraction>
     */
    private function selectedRecommendationInteractions(
        User $user,
        Builder $query,
        array $interactionIds
    ): Builder {
        $this->ownership
            ->recommendationInteractions(
                $user,
                $interactionIds
            );

        return $query->whereKey(
            $interactionIds
        );
    }

    private function requiredDays(
        ?int $olderThanDays
    ): int {
        if ($olderThanDays === null) {
            throw new InvalidArgumentException(
                'Umur data wajib tersedia untuk cakupan older.'
            );
        }

        return $olderThanDays;
    }
}
