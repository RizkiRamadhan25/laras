<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Services\BudgetManagementService;
use App\Services\BudgetPeriodService;
use App\Services\BudgetService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BudgetConcurrencyHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_guard_rejects_two_active_budgets_for_same_category(): void
    {
        $user = $this->user();
        $category = $this->category($user);

        $this->budget(
            $user,
            $category
        );

        $this->expectException(
            UniqueConstraintViolationException::class
        );

        Budget::query()->create([
            'user_id' => $user->id,
            'finance_category_id' =>
                $category->id,
            'active_finance_category_id' =>
                $category->id,
            'name' => 'Duplikat langsung',
            'amount' => '500000.00',
            'period_type' => 'monthly',
            'warning_threshold_percent' =>
                '80.00',
            'start_date' => '2026-08-01',
            'end_date' => null,
            'is_recurring' => true,
            'is_active' => true,
        ]);
    }

    public function test_multiple_inactive_budgets_for_same_category_are_allowed(): void
    {
        $user = $this->user();
        $category = $this->category($user);

        $first = $this->budget(
            $user,
            $category
        );

        app(BudgetManagementService::class)
            ->deactivate(
                $user,
                $first
            );

        $second = $this->budget(
            $user,
            $category,
            'Anggaran Kedua'
        );

        app(BudgetManagementService::class)
            ->deactivate(
                $user,
                $second
            );

        $this->assertSame(
            2,
            Budget::query()->count()
        );

        $this->assertSame(
            0,
            Budget::query()
                ->whereNotNull(
                    'active_finance_category_id'
                )
                ->count()
        );
    }

    public function test_period_sync_is_idempotent_for_same_date_range(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );

        $user = $this->user();
        $category = $this->category($user);
        $budget = $this->budget(
            $user,
            $category
        );

        $service = app(
            BudgetPeriodService::class
        );

        $service->sync(
            $budget,
            '100000.00',
            CarbonImmutable::parse(
                '2026-08-15'
            )
        );

        $latest = $service->sync(
            $budget,
            '250000.00',
            CarbonImmutable::parse(
                '2026-08-20'
            )
        );

        $this->assertSame(
            1,
            $budget->periods()->count()
        );

        $this->assertSame(
            '250000.00',
            $latest->used_amount
        );

        $this->assertSame(
            '750000.00',
            $latest->remaining_amount
        );
    }

    public function test_conflicting_reactivation_returns_validation_error(): void
    {
        $user = $this->user();
        $category = $this->category($user);

        $first = $this->budget(
            $user,
            $category,
            'Anggaran Pertama'
        );

        app(BudgetManagementService::class)
            ->deactivate(
                $user,
                $first
            );

        $this->budget(
            $user,
            $category,
            'Anggaran Aktif'
        );

        try {
            app(BudgetManagementService::class)
                ->activate(
                    $user,
                    $first
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

        $this->assertFalse(
            $first->fresh()->is_active
        );
    }

    private function user(): User
    {
        return User::factory()->create([
            'onboarding_completed_at' =>
                now(),
            'is_active' => true,
        ]);
    }

    private function category(
        User $user
    ): FinanceCategory {
        $category = new FinanceCategory();

        $category->forceFill([
            'user_id' => $user->id,
            'flow_type' => 'expense',
            'name' => 'Makanan',
            'icon' => 'wallet-cards',
            'color' => '#2563EB',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $category->save();

        return $category;
    }

    private function budget(
        User $user,
        FinanceCategory $category,
        string $name = 'Anggaran Bulanan'
    ): Budget {
        return app(BudgetService::class)
            ->create(
                $user,
                $category,
                [
                    'name' => $name,
                    'amount' => '1000000.00',
                    'period_type' =>
                        BudgetPeriodType::Monthly
                            ->value,
                    'warning_threshold_percent' =>
                        '80.00',
                    'start_date' =>
                        '2026-08-01',
                ]
            );
    }
}
