<?php

namespace App\Services;

use App\Enums\BudgetAlertLevel;
use App\Enums\BudgetPeriodStatus;
use App\Models\BudgetPeriod;
use App\Notifications\BudgetExceeded;
use App\Notifications\BudgetWarningReached;
use Illuminate\Support\Facades\DB;

class BudgetAlertService
{
    public function __construct(
        private readonly BudgetPeriodService $periodService
    ) {
    }

    public function notifyForPeriod(
        BudgetPeriod $period
    ): void {
        $period->loadMissing([
            'budget.user.preference',
            'budget.financeCategory',
        ]);

        $budget = $period->budget;

        if (
            $budget === null
            || ! $budget->is_active
            || $period->status
                !== BudgetPeriodStatus::Active
        ) {
            return;
        }

        $level = $this->periodService
            ->alertLevel($period);

        if ($level === BudgetAlertLevel::Safe) {
            return;
        }

        $user = $budget->user;

        if ($user === null) {
            return;
        }

        DB::transaction(
            function () use (
                $period,
                $budget,
                $user,
                $level
            ): void {
                $inserted = DB::table(
                    'budget_alert_events'
                )->insertOrIgnore([
                    'budget_period_id' =>
                        $period->id,

                    'alert_level' =>
                        $level->value,

                    'notified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted !== 1) {
                    return;
                }

                $currencyCode = $user
                    ->preference
                    ?->currency_code
                    ?? config(
                        'laras.defaults.currency_code',
                        'IDR'
                    );

                $categoryName = $budget
                    ->financeCategory
                    ?->name
                    ?? 'Tanpa kategori';

                $deduplicationKey = sprintf(
                    'budget:%d:period:%d:level:%s',
                    $budget->id,
                    $period->id,
                    $level->value
                );

                if (
                    $level
                    === BudgetAlertLevel::Exceeded
                ) {
                    $user->notify(
                        new BudgetExceeded(
                            budgetId: $budget->id,

                            budgetPeriodId:
                                $period->id,

                            budgetName:
                                $budget->name,

                            categoryName:
                                $categoryName,

                            usedAmount:
                                $period->used_amount,

                            budgetAmount:
                                $period->budget_amount,

                            remainingAmount:
                                $period->remaining_amount,

                            usagePercent:
                                $period->usage_percent,

                            currencyCode:
                                $currencyCode,

                            periodStart: $period
                                ->period_start
                                ->toDateString(),

                            periodEnd: $period
                                ->period_end
                                ->toDateString(),

                            deduplicationKey:
                                $deduplicationKey
                        )
                    );

                    return;
                }

                $user->notify(
                    new BudgetWarningReached(
                        budgetId: $budget->id,

                        budgetPeriodId:
                            $period->id,

                        budgetName:
                            $budget->name,

                        categoryName:
                            $categoryName,

                        usedAmount:
                            $period->used_amount,

                        budgetAmount:
                            $period->budget_amount,

                        usagePercent:
                            $period->usage_percent,

                        warningThresholdPercent:
                            $budget
                                ->warning_threshold_percent,

                        currencyCode:
                            $currencyCode,

                        periodStart: $period
                            ->period_start
                            ->toDateString(),

                        periodEnd: $period
                            ->period_end
                            ->toDateString(),

                        deduplicationKey:
                            $deduplicationKey
                    )
                );
            },
            3
        );
    }
}
