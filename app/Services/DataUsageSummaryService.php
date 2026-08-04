<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Budget;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DataUsageSummaryService
{
    /**
     * @return array<string, array<string, int>>
     */
    public function summarize(
        User $user
    ): array {
        $profilePhotoExists =
            $user->profile_photo_path !== null
            && Storage::disk('public')->exists(
                $user->profile_photo_path
            );

        $profilePhotoBytes = $profilePhotoExists
            ? Storage::disk('public')->size(
                $user->profile_photo_path
            )
            : 0;

        return [
            'accounts' => [
                'active' => Account::query()
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->count(),

                'archived' => Account::onlyTrashed()
                    ->where('user_id', $user->id)
                    ->count(),
            ],

            'transactions' => [
                'recorded' => Transaction::query()
                    ->where('user_id', $user->id)
                    ->count(),

                'archived' => Transaction::onlyTrashed()
                    ->where('user_id', $user->id)
                    ->count(),
            ],

            'activities' => [
                'current' => Activity::query()
                    ->where('user_id', $user->id)
                    ->count(),

                'archived' => Activity::onlyTrashed()
                    ->where('user_id', $user->id)
                    ->count(),
            ],

            'subscriptions' => [
                'active' => Subscription::query()
                    ->where('user_id', $user->id)
                    ->where(
                        'status',
                        SubscriptionStatus::Active->value
                    )
                    ->count(),

                'paused' => Subscription::query()
                    ->where('user_id', $user->id)
                    ->where(
                        'status',
                        SubscriptionStatus::Paused->value
                    )
                    ->count(),

                'archived' => Subscription::onlyTrashed()
                    ->where('user_id', $user->id)
                    ->count(),
            ],

            'budgets' => [
                'active' => Budget::query()
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->count(),

                'archived' => Budget::onlyTrashed()
                    ->where('user_id', $user->id)
                    ->count(),
            ],

            'notifications' => [
                'total' => $user
                    ->notifications()
                    ->count(),

                'unread' => $user
                    ->unreadNotifications()
                    ->count(),
            ],

            'recommendations' => [
                'interactions' => $user
                    ->recommendationInteractions()
                    ->count(),
            ],

            'files' => [
                'count' => $profilePhotoExists
                    ? 1
                    : 0,

                'bytes' => (int) $profilePhotoBytes,
            ],
        ];
    }
}
