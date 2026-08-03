<?php

namespace App\Services;

use App\Enums\DataDeletionScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DataDeletionService
{
    public function __construct(
        private readonly DataDeletionScopeService $scopes
    ) {}

    /**
     * @param  list<string>  $notificationIds
     */
    public function deleteNotifications(
        User $user,
        DataDeletionScope $scope,
        array $notificationIds = [],
        ?int $olderThanDays = null
    ): int {
        return DB::transaction(
            fn (): int => $this->scopes
                ->notifications(
                    user: $user,
                    scope: $scope,
                    notificationIds: $notificationIds,
                    olderThanDays: $olderThanDays
                )
                ->delete(),
            3
        );
    }

    /**
     * @param  list<int>  $interactionIds
     */
    public function deleteRecommendationInteractions(
        User $user,
        DataDeletionScope $scope,
        array $interactionIds = [],
        ?int $olderThanDays = null
    ): int {
        return DB::transaction(
            fn (): int => $this->scopes
                ->recommendationInteractions(
                    user: $user,
                    scope: $scope,
                    interactionIds: $interactionIds,
                    olderThanDays: $olderThanDays
                )
                ->delete(),
            3
        );
    }
}
