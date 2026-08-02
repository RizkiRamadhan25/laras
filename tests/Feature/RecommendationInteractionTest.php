<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\RecommendationInteractionType;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\PersonalRecommendationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_recommendation_records_interaction(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->user();

        $activity = Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Menyelesaikan tugas',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addHour(),
            ]);

        $item = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        )['items']->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'recommendations.open',
                    $item['key']
                )
            )
            ->assertRedirectToRoute(
                'activities.edit',
                $activity->id
            );

        $this->assertDatabaseHas(
            'recommendation_interactions',
            [
                'user_id' => $user->id,

                'recommendation_key' => $item['key'],

                'interaction_type' => RecommendationInteractionType::Opened->value,
            ]
        );
    }

    public function test_followed_up_recommendation_is_temporarily_suppressed(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->user();

        Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Tugas penting',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addHour(),
            ]);

        $item = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        )['items']->firstOrFail();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'recommendations.feedback',
                    $item['key']
                ),
                [
                    'interaction_type' => RecommendationInteractionType::FollowedUp->value,
                ]
            )
            ->assertRedirectToRoute(
                'recommendations.index'
            )
            ->assertSessionHas('status');

        $recommendations = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $this->assertFalse(
            $recommendations['items']
                ->contains(
                    'key',
                    $item['key']
                )
        );

        $this->assertSame(
            1,
            $recommendations['summary'][
                'suppressed'
            ]
        );
    }

    public function test_dismissed_recommendation_returns_after_twenty_four_hours(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->user();

        Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Tugas mendatang',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addDays(3),
            ]);

        $item = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        )['items']->firstOrFail();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'recommendations.feedback',
                    $item['key']
                ),
                [
                    'interaction_type' => RecommendationInteractionType::Dismissed->value,
                ]
            )
            ->assertRedirect();

        $hidden = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        );

        $this->assertFalse(
            $hidden['items']->contains(
                'key',
                $item['key']
            )
        );

        $later = $reference->addHours(25);

        $visibleAgain = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $later
        );

        $this->assertTrue(
            $visibleAgain['items']->contains(
                'key',
                $item['key']
            )
        );
    }

    public function test_user_cannot_feedback_another_users_recommendation(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->user();
        $otherUser = $this->user();

        Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $otherUser->id,

                'title' => 'Data pengguna lain',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addHour(),
            ]);

        $otherItem = app(
            PersonalRecommendationService::class
        )->build(
            user: $otherUser,
            reference: $reference
        )['items']->firstOrFail();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'recommendations.feedback',
                    $otherItem['key']
                ),
                [
                    'interaction_type' => RecommendationInteractionType::Irrelevant->value,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'recommendation_interactions',
            0
        );
    }

    public function test_history_only_displays_owned_interactions(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-10 09:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        $user = $this->user();
        $otherUser = $this->user();

        $userActivity = Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $user->id,
                'title' => 'Rekomendasi sendiri',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addHour(),
            ]);

        Activity::factory()
            ->urgent()
            ->create([
                'user_id' => $otherUser->id,

                'title' => 'Rekomendasi orang lain',

                'status' => ActivityStatus::Planned,

                'due_at' => $reference->addHour(),
            ]);

        $ownItem = app(
            PersonalRecommendationService::class
        )->build(
            user: $user,
            reference: $reference
        )['items']->firstOrFail();

        $otherItem = app(
            PersonalRecommendationService::class
        )->build(
            user: $otherUser,
            reference: $reference
        )['items']->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'recommendations.open',
                    $ownItem['key']
                )
            )
            ->assertRedirectToRoute(
                'activities.edit',
                $userActivity->id
            );

        $this
            ->actingAs($otherUser)
            ->get(
                route(
                    'recommendations.open',
                    $otherItem['key']
                )
            )
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'recommendations.history'
                )
            )
            ->assertOk()
            ->assertSee(
                'Rekomendasi sendiri'
            )
            ->assertDontSee(
                'Rekomendasi orang lain'
            );
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
