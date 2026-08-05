<?php

namespace Tests\Feature;

use App\Enums\RecommendationInteractionType;
use App\Models\RecommendationInteraction;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class DataDeletionInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_center_renders_owned_deletion_controls(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $ownedNotification = $this->notification(
            $user,
            'Netflix'
        );

        $foreignNotification = $this->notification(
            $otherUser,
            'Spotify'
        );

        $this
            ->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-deletion-manager', false)
            ->assertSee('data-deletion-select-page', false)
            ->assertSee('data-deletion-dialog', false)
            ->assertSee(
                route('notifications.deletion-preview'),
                false
            )
            ->assertSee(
                'value="'.$ownedNotification->id.'"',
                false
            )
            ->assertDontSee(
                'value="'.$foreignNotification->id.'"',
                false
            )
            ->assertDontSee('confirm(', false);
    }

    public function test_recommendation_history_renders_owned_deletion_controls(): void
    {
        $user = $this->user();
        $otherUser = $this->user();

        $ownedInteraction = $this->interaction(
            $user,
            'Riwayat sendiri'
        );

        $foreignInteraction = $this->interaction(
            $otherUser,
            'Riwayat pengguna lain'
        );

        $this
            ->actingAs($user)
            ->get(route('recommendations.history'))
            ->assertOk()
            ->assertSee('data-deletion-manager', false)
            ->assertSee('data-deletion-select-page', false)
            ->assertSee('data-deletion-dialog', false)
            ->assertSee(
                route(
                    'recommendations.history.deletion-preview'
                ),
                false
            )
            ->assertSee(
                'value="'.$ownedInteraction->id.'"',
                false
            )
            ->assertDontSee(
                'value="'.$foreignInteraction->id.'"',
                false
            )
            ->assertDontSee('confirm(', false);
    }

    public function test_empty_pages_keep_dialog_but_hide_bulk_selection_controls(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-deletion-dialog', false)
            ->assertDontSee(
                'data-deletion-select-page',
                false
            );

        $this
            ->actingAs($user)
            ->get(route('recommendations.history'))
            ->assertOk()
            ->assertSee('data-deletion-dialog', false)
            ->assertDontSee(
                'data-deletion-select-page',
                false
            );
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
        string $title
    ): RecommendationInteraction {
        return RecommendationInteraction::query()
            ->create([
                'user_id' => $user->id,
                'recommendation_key' => str($title)
                    ->slug()
                    ->append('-'.str()->random(6))
                    ->toString(),
                'recommendation_kind' => 'activity',
                'interaction_type' => RecommendationInteractionType::Opened,
                'title' => $title,
                'snapshot' => [
                    'message' => $title,
                ],
                'occurred_at' => now(),
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
