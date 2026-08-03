<?php

namespace App\Services;

use App\Models\RecommendationInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

class OwnedResourceGuard
{
    public function notification(
        User $user,
        string $notificationId
    ): DatabaseNotification {
        return $user->notifications()
            ->findOrFail($notificationId);
    }

    /**
     * @param  list<string>  $notificationIds
     * @return Collection<int, DatabaseNotification>
     */
    public function notifications(
        User $user,
        array $notificationIds
    ): Collection {
        $ids = collect($notificationIds)
            ->unique()
            ->values();

        $notifications = $user->notifications()
            ->whereKey($ids->all())
            ->get();

        if ($notifications->count() !== $ids->count()) {
            throw (new ModelNotFoundException)
                ->setModel(
                    DatabaseNotification::class,
                    $ids->all()
                );
        }

        return $notifications;
    }

    public function recommendationInteraction(
        User $user,
        int $interactionId
    ): RecommendationInteraction {
        return $user->recommendationInteractions()
            ->findOrFail($interactionId);
    }

    /**
     * @param  list<int>  $interactionIds
     * @return Collection<int, RecommendationInteraction>
     */
    public function recommendationInteractions(
        User $user,
        array $interactionIds
    ): Collection {
        $ids = collect($interactionIds)
            ->map(
                fn (mixed $id): int => (int) $id
            )
            ->unique()
            ->values();

        $interactions = $user
            ->recommendationInteractions()
            ->whereKey($ids->all())
            ->get();

        if ($interactions->count() !== $ids->count()) {
            throw (new ModelNotFoundException)
                ->setModel(
                    RecommendationInteraction::class,
                    $ids->all()
                );
        }

        return $interactions;
    }
}
