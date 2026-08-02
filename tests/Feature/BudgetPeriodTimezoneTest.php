<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodStatus;
use App\Enums\BudgetPeriodType;
use App\Enums\FinanceFlowType;
use App\Models\BudgetPeriod;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetPeriodTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_makassar_period_becomes_active_at_local_midnight(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-09-30 23:30:00',
                'Asia/Jakarta'
            )
        );

        $period = $this->createOctoberPeriod(
            'Asia/Makassar'
        );

        $this->assertSame(
            BudgetPeriodStatus::Active,
            $period->status
        );
    }

    public function test_jayapura_period_becomes_active_at_local_midnight(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-09-30 22:30:00',
                'Asia/Jakarta'
            )
        );

        $period = $this->createOctoberPeriod(
            'Asia/Jayapura'
        );

        $this->assertSame(
            BudgetPeriodStatus::Active,
            $period->status
        );
    }

    public function test_jakarta_period_remains_upcoming_before_local_midnight(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-09-30 23:30:00',
                'Asia/Jakarta'
            )
        );

        $period = $this->createOctoberPeriod(
            'Asia/Jakarta'
        );

        $this->assertSame(
            BudgetPeriodStatus::Upcoming,
            $period->status
        );
    }

    private function createOctoberPeriod(
        string $timezone
    ): BudgetPeriod {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => $timezone,
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        $category = FinanceCategory::query()
            ->create([
                'user_id' => $user->id,
                'flow_type' => FinanceFlowType::Expense,
                'name' => 'Makanan',
                'icon' => 'utensils',
                'color' => '#F97316',
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]);

        $budget = app(
            BudgetService::class
        )->create(
            $user,
            $category,
            [
                'name' => 'Anggaran Oktober',
                'amount' => '1000000.00',
                'period_type' => BudgetPeriodType::Monthly
                    ->value,
                'warning_threshold_percent' => '80.00',
                'start_date' => '2026-10-01',
            ]
        );

        return $budget
            ->periods()
            ->firstOrFail();
    }
}
