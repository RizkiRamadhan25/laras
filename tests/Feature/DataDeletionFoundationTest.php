<?php

namespace Tests\Feature;

use App\Enums\RecommendationInteractionType;
use App\Models\RecommendationInteraction;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\SubscriptionRenewalReminder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class DataDeletionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_preview_only_counts_owned_records(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $readNotification = $this->notification(
            $user,
            'Netflix'
        );

        $readNotification->markAsRead();

        $this->notification(
            $user,
            'Spotify'
        );

        $this->notification(
            $otherUser,
            'YouTube'
        );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'notifications.deletion-preview'
                ),
                [
                    'scope' => 'all',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.resource',
                'notifications'
            )
            ->assertJsonPath(
                'data.count',
                2
            )
            ->assertJsonPath(
                'data.details.read',
                1
            )
            ->assertJsonPath(
                'data.details.unread',
                1
            );
    }

    public function test_selected_notification_preview_rejects_foreign_ids(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $foreignNotification = $this->notification(
            $otherUser,
            'Spotify'
        );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'notifications.deletion-preview'
                ),
                [
                    'scope' => 'selected',
                    'notification_ids' => [
                        $foreignNotification->id,
                    ],
                ]
            )
            ->assertNotFound();
    }

    public function test_user_can_delete_only_read_notifications(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $readNotification = $this->notification(
            $user,
            'Netflix'
        );

        $readNotification->markAsRead();

        $unreadNotification = $this->notification(
            $user,
            'Spotify'
        );

        $foreignReadNotification = $this->notification(
            $otherUser,
            'YouTube'
        );

        $foreignReadNotification->markAsRead();

        $this
            ->actingAs($user)
            ->delete(
                route('notifications.purge'),
                [
                    'scope' => 'read',
                ]
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing(
            'notifications',
            [
                'id' => $readNotification->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' => $unreadNotification->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' => $foreignReadNotification->id,
            ]
        );
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $notification = $this->notification(
            $otherUser,
            'Spotify'
        );

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'notifications.destroy',
                    $notification->id
                )
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' => $notification->id,
            ]
        );
    }

    public function test_recommendation_preview_only_counts_owned_history(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $this->interaction(
            $user,
            RecommendationInteractionType::Opened,
            'Rekomendasi sendiri'
        );

        $this->interaction(
            $user,
            RecommendationInteractionType::Dismissed,
            'Rekomendasi ditunda'
        );

        $this->interaction(
            $otherUser,
            RecommendationInteractionType::Opened,
            'Rekomendasi pengguna lain'
        );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'recommendations.history.deletion-preview'
                ),
                [
                    'scope' => 'all',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.resource',
                'recommendation_interactions'
            )
            ->assertJsonPath(
                'data.count',
                2
            )
            ->assertJsonPath(
                'data.details.opened',
                1
            )
            ->assertJsonPath(
                'data.details.dismissed',
                1
            );
    }

    public function test_user_can_delete_old_recommendation_history(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-03 20:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->user();
        $otherUser = $this->user();

        $oldInteraction = $this->interaction(
            $user,
            RecommendationInteractionType::Opened,
            'Riwayat lama',
            $reference->subDays(45)
        );

        $recentInteraction = $this->interaction(
            $user,
            RecommendationInteractionType::FollowedUp,
            'Riwayat baru',
            $reference->subDays(5)
        );

        $foreignOldInteraction = $this->interaction(
            $otherUser,
            RecommendationInteractionType::Opened,
            'Riwayat lama pengguna lain',
            $reference->subDays(60)
        );

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'recommendations.history.purge'
                ),
                [
                    'scope' => 'older',
                    'older_than_days' => 30,
                ]
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing(
            'recommendation_interactions',
            [
                'id' => $oldInteraction->id,
            ]
        );

        $this->assertDatabaseHas(
            'recommendation_interactions',
            [
                'id' => $recentInteraction->id,
            ]
        );

        $this->assertDatabaseHas(
            'recommendation_interactions',
            [
                'id' => $foreignOldInteraction->id,
            ]
        );
    }

    public function test_user_can_delete_selected_recommendation_history(): void
    {
        $user = $this->user();

        $selected = $this->interaction(
            $user,
            RecommendationInteractionType::Opened,
            'Hapus data ini'
        );

        $kept = $this->interaction(
            $user,
            RecommendationInteractionType::Dismissed,
            'Pertahankan data ini'
        );

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'recommendations.history.purge'
                ),
                [
                    'scope' => 'selected',
                    'interaction_ids' => [
                        $selected->id,
                    ],
                ]
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing(
            'recommendation_interactions',
            [
                'id' => $selected->id,
            ]
        );

        $this->assertDatabaseHas(
            'recommendation_interactions',
            [
                'id' => $kept->id,
            ]
        );
    }

    public function test_user_cannot_delete_another_users_recommendation_history(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $interaction = $this->interaction(
            $otherUser,
            RecommendationInteractionType::Opened,
            'Riwayat pengguna lain'
        );

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'recommendations.history.destroy',
                    $interaction->id
                )
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'recommendation_interactions',
            [
                'id' => $interaction->id,
            ]
        );
    }

    public function test_selected_scope_requires_resource_ids(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'notifications.deletion-preview'
                ),
                [
                    'scope' => 'selected',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'notification_ids',
            ]);

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'recommendations.history.deletion-preview'
                ),
                [
                    'scope' => 'selected',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'interaction_ids',
            ]);
    }

    private function notification(
        User $user,
        string $subscriptionName
    ): DatabaseNotification {
        $existingIds = $user->notifications()
            ->pluck('id');

        $user->notify(
            new SubscriptionRenewalReminder(
                subscriptionId: 1,
                billingId: 1,
                subscriptionName: $subscriptionName,
                amount: '59000.00',
                currencyCode: 'IDR',
                scheduledFor: '2026-08-10',
                daysBefore: 3,
                accountName: 'BCA'
            )
        );

        return $user->notifications()
            ->whereNotIn(
                'id',
                $existingIds
            )
            ->firstOrFail();
    }

    private function interaction(
        User $user,
        RecommendationInteractionType $type,
        string $title,
        ?CarbonImmutable $occurredAt = null
    ): RecommendationInteraction {
        return RecommendationInteraction::query()
            ->create([
                'user_id' => $user->id,
                'recommendation_key' => str($title)
                    ->slug()
                    ->append('-'.str()->random(6))
                    ->toString(),
                'recommendation_kind' => 'activity',
                'interaction_type' => $type,
                'title' => $title,
                'snapshot' => [
                    'message' => $title,
                ],
                'occurred_at' => $occurredAt ?? now(),
            ]);
    }

    private function user(): User
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
