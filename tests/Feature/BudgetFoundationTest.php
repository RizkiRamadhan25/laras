<?php

namespace Tests\Feature;

use App\Enums\BudgetAlertLevel;
use App\Enums\BudgetPeriodStatus;
use App\Enums\BudgetPeriodType;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Services\BudgetPeriodService;
use App\Services\BudgetService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BudgetFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_budget_creates_initial_period(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Makanan'
        );

        $budget = app(
            BudgetService::class
        )->create(
            $user,
            $category,
            [
                'name' =>
                    'Anggaran Makanan',

                'amount' =>
                    '1000000.00',

                'period_type' =>
                    BudgetPeriodType::Monthly
                        ->value,

                'warning_threshold_percent' =>
                    '80.00',

                'start_date' =>
                    '2026-08-01',
            ]
        );

        $this->assertDatabaseHas(
            'budgets',
            [
                'id' => $budget->id,
                'user_id' => $user->id,

                'finance_category_id' =>
                    $category->id,

                'name' =>
                    'Anggaran Makanan',

                'amount' =>
                    '1000000.00',

                'period_type' =>
                    'monthly',

                'is_recurring' =>
                    true,

                'is_active' =>
                    true,
            ]
        );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '2026-08-01',
            $period
                ->period_start
                ->toDateString()
        );

        $this->assertSame(
            '2026-08-31',
            $period
                ->period_end
                ->toDateString()
        );

        $this->assertSame(
            '1000000.00',
            $period->budget_amount
        );

        $this->assertSame(
            '0.00',
            $period->used_amount
        );

        $this->assertSame(
            '1000000.00',
            $period->remaining_amount
        );

        $this->assertSame(
            '0.00',
            $period->usage_percent
        );

        $this->assertSame(
            BudgetPeriodStatus::Active,
            $period->status
        );
    }

    public function test_budget_rejects_non_expense_category(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'income',
            'Freelance'
        );

        try {
            app(
                BudgetService::class
            )->create(
                $user,
                $category,
                $this->monthlyPayload()
            );

            $this->fail(
                'ValidationException tidak dilempar.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'finance_category_id',
                $exception->errors()
            );

            $this->assertSame(
                'Anggaran hanya dapat menggunakan kategori pengeluaran.',
                $exception->errors()[
                    'finance_category_id'
                ][0]
            );
        }

        $this->assertDatabaseCount(
            'budgets',
            0
        );
    }

    public function test_budget_rejects_category_owned_by_another_user(): void
    {
        $user = $this->user();

        $otherUser =
            $this->user();

        $category = $this->category(
            $otherUser,
            'expense',
            'Transportasi'
        );

        try {
            app(
                BudgetService::class
            )->create(
                $user,
                $category,
                $this->monthlyPayload()
            );

            $this->fail(
                'ValidationException tidak dilempar.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'finance_category_id',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'budgets',
            0
        );
    }

    public function test_category_cannot_have_two_active_budgets(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Hiburan'
        );

        $service = app(
            BudgetService::class
        );

        $service->create(
            $user,
            $category,
            $this->monthlyPayload()
        );

        $this->expectException(
            ValidationException::class
        );

        $service->create(
            $user,
            $category,
            array_merge(
                $this->monthlyPayload(),
                [
                    'name' =>
                        'Anggaran Hiburan Kedua',
                ]
            )
        );
    }

    public function test_usage_level_changes_from_safe_to_warning_and_exceeded(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Belanja'
        );

        $budget = app(
            BudgetService::class
        )->create(
            $user,
            $category,
            $this->monthlyPayload()
        );

        $periodService = app(
            BudgetPeriodService::class
        );

        $safe = $periodService->sync(
            $budget,
            '799999.99',
            CarbonImmutable::parse(
                '2026-08-15'
            )
        );

        $this->assertSame(
            BudgetAlertLevel::Safe,
            $periodService->alertLevel(
                $safe
            )
        );

        $warning =
            $periodService->sync(
                $budget,
                '800000.00',
                CarbonImmutable::parse(
                    '2026-08-15'
                )
            );

        $this->assertSame(
            '80.00',
            $warning->usage_percent
        );

        $this->assertSame(
            BudgetAlertLevel::Warning,
            $periodService->alertLevel(
                $warning
            )
        );

        $exceeded =
            $periodService->sync(
                $budget,
                '1100000.00',
                CarbonImmutable::parse(
                    '2026-08-15'
                )
            );

        $this->assertSame(
            '110.00',
            $exceeded->usage_percent
        );

        $this->assertSame(
            '-100000.00',
            $exceeded->remaining_amount
        );

        $this->assertSame(
            BudgetAlertLevel::Exceeded,
            $periodService->alertLevel(
                $exceeded
            )
        );
    }

    public function test_custom_budget_uses_exact_date_range(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Pendidikan'
        );

        $budget = app(
            BudgetService::class
        )->create(
            $user,
            $category,
            [
                'name' =>
                    'Anggaran Semester Pendek',

                'amount' =>
                    '2500000.00',

                'period_type' =>
                    BudgetPeriodType::Custom
                        ->value,

                'warning_threshold_percent' =>
                    '75.00',

                'start_date' =>
                    '2026-08-10',

                'end_date' =>
                    '2026-09-20',
            ]
        );

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            '2026-08-10',
            $period
                ->period_start
                ->toDateString()
        );

        $this->assertSame(
            '2026-09-20',
            $period
                ->period_end
                ->toDateString()
        );

        $this->assertFalse(
            $budget->is_recurring
        );
    }

    public function test_monthly_budget_generates_a_new_period_for_new_month(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Internet'
        );

        $budget = app(
            BudgetService::class
        )->create(
            $user,
            $category,
            $this->monthlyPayload()
        );

        app(
            BudgetPeriodService::class
        )->sync(
            $budget,
            '150000.00',
            CarbonImmutable::parse(
                '2026-09-10'
            )
        );

        $this->assertSame(
            2,
            $budget
                ->periods()
                ->count()
        );

        $septemberPeriod = $budget
            ->periods()
            ->whereDate(
                'period_start',
                '2026-09-01'
            )
            ->firstOrFail();

        $this->assertSame(
            '2026-09-01',
            $septemberPeriod
                ->period_start
                ->toDateString()
        );

        $this->assertSame(
            '2026-09-30',
            $septemberPeriod
                ->period_end
                ->toDateString()
        );

        $this->assertSame(
            '150000.00',
            $septemberPeriod->used_amount
        );

        $this->assertSame(
            '850000.00',
            $septemberPeriod
                ->remaining_amount
        );

        $this->assertSame(
            '15.00',
            $septemberPeriod
                ->usage_percent
        );
    }

    private function user(): User
    {
        return User::factory()->create([
            'onboarding_completed_at' =>
                now(),

            'is_active' =>
                true,
        ]);
    }

    private function category(
        User $user,
        string $flowType,
        string $name,
        bool $isActive = true
    ): FinanceCategory {
        $category = new FinanceCategory();

        $category->forceFill([
            'user_id' =>
                $user->id,

            'flow_type' =>
                $flowType,

            'name' =>
                $name,

            'icon' =>
                'wallet-cards',

            'color' =>
                '#2563EB',

            'is_system' =>
                false,

            'is_active' =>
                $isActive,

            'sort_order' =>
                0,
        ]);

        $category->save();

        return $category;
    }

    /**
     * @return array<string, string>
     */
    private function monthlyPayload(): array
    {
        return [
            'name' =>
                'Anggaran Bulanan',

            'amount' =>
                '1000000.00',

            'period_type' =>
                BudgetPeriodType::Monthly
                    ->value,

            'warning_threshold_percent' =>
                '80.00',

            'start_date' =>
                '2026-08-01',
        ];
    }
}
