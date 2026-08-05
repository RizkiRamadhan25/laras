<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_global_search(): void
    {
        $this
            ->getJson(
                route('search.global', [
                    'q' => 'dashboard',
                ])
            )
            ->assertUnauthorized();
    }

    public function test_global_search_requires_at_least_two_characters(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->getJson(
                route('search.global', [
                    'q' => 'a',
                ])
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_search_returns_matching_navigation(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->getJson(
                route('search.global', [
                    'q' => 'pengaturan',
                ])
            )
            ->assertOk()
            ->assertJsonPath(
                'groups.0.key',
                'navigation'
            )
            ->assertJsonFragment([
                'title' => 'Pengaturan',
                'url' => route('settings.index'),
            ]);
    }

    public function test_search_returns_owned_resources(): void
    {
        $user = $this->completedUser();

        Account::factory()->for($user)->create([
            'name' => 'BCA Dana Harian',
        ]);

        Activity::factory()->for($user)->create([
            'title' => 'Review laporan Laras',
        ]);

        Transaction::factory()->for($user)->create([
            'description' => 'Pembelian domain Laras',
        ]);

        Subscription::factory()->for($user)->create([
            'name' => 'Laras Cloud',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(
                route('search.global', [
                    'q' => 'Laras',
                ])
            )
            ->assertOk();

        $response
            ->assertJsonFragment([
                'title' => 'Review laporan Laras',
            ])
            ->assertJsonFragment([
                'title' => 'Pembelian domain Laras',
            ])
            ->assertJsonFragment([
                'title' => 'Laras Cloud',
            ]);
    }

    public function test_search_never_returns_another_users_data(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        Account::factory()->for($user)->create([
            'name' => 'Rekening Pribadi Aman',
        ]);

        Account::factory()->for($otherUser)->create([
            'name' => 'Rahasia Milik User Lain',
        ]);

        $this
            ->actingAs($user)
            ->getJson(
                route('search.global', [
                    'q' => 'Rahasia',
                ])
            )
            ->assertOk()
            ->assertJsonMissing([
                'title' => 'Rahasia Milik User Lain',
            ]);
    }

    public function test_each_resource_group_is_limited_to_five_results(): void
    {
        $user = $this->completedUser();

        Activity::factory()
            ->count(7)
            ->for($user)
            ->sequence(
                fn ($sequence): array => [
                    'title' => 'Agenda rutin '
                        .$sequence->index,
                ]
            )
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson(
                route('search.global', [
                    'q' => 'Agenda',
                ])
            )
            ->assertOk();

        $activityGroup = collect(
            $response->json('groups')
        )->firstWhere('key', 'activities');

        $this->assertNotNull($activityGroup);
        $this->assertCount(
            5,
            $activityGroup['items']
        );
    }

    public function test_topbar_contains_global_search_interface(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                'data-laras-global-search',
                false
            )
            ->assertSee(
                'data-global-search-dialog',
                false
            )
            ->assertSee(
                route('search.global'),
                false
            )
            ->assertDontSee(
                'Pencarian akan tersedia pada pengembangan berikutnya'
            );
    }

    private function completedUser(): User
    {
        return User::factory()->create([
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);
    }
}
