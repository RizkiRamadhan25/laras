<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\DataUsageSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataUsageSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_only_counts_owned_data(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $archivedAccount =
            Account::factory()->create([
                'user_id' => $user->id,
                'is_active' => false,
            ]);

        $archivedAccount->delete();

        Account::factory()->create([
            'user_id' => $otherUser->id,
            'is_active' => true,
        ]);

        Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $archivedActivity =
            Activity::factory()->create([
                'user_id' => $user->id,
            ]);

        $archivedActivity->delete();

        Activity::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $summary = app(
            DataUsageSummaryService::class
        )->summarize($user);

        $this->assertSame(
            1,
            $summary['accounts']['active']
        );

        $this->assertSame(
            1,
            $summary['accounts']['archived']
        );

        $this->assertSame(
            1,
            $summary['activities']['current']
        );

        $this->assertSame(
            1,
            $summary['activities']['archived']
        );
    }

    public function test_settings_page_exposes_data_usage_summary(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(
                'data-data-usage-summary',
                false
            )
            ->assertSee(
                'data-data-usage-item="accounts"',
                false
            )
            ->assertSee(
                'data-data-usage-item="activities"',
                false
            )
            ->assertSee(
                'data-data-usage-item="notifications"',
                false
            )
            ->assertSee(
                'data-data-usage-item="files"',
                false
            );
    }

    private function completedUser(): User
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
