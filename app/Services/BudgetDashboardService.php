<?php

namespace App\Services;

use App\Enums\BudgetAlertLevel;
use App\Models\Budget;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class BudgetDashboardService
{
    public function __construct(
        private readonly BudgetPeriodService $periodService
    ) {}

    /**
     * @return array{
     *     generated_at: CarbonImmutable,
     *     currency_code: string,
     *     items: Collection<int, array<string, mixed>>,
     *     attention_items: Collection<int, array<string, mixed>>,
     *     summary: array{
     *         active: int,
     *         safe: int,
     *         warning: int,
     *         exceeded: int,
     *         total_limit: string,
     *         total_used: string,
     *         total_remaining: string
     *     }
     * }
     */
    public function build(
        User $user,
        ?DateTimeInterface $reference = null
    ): array {
        $user->loadMissing('preference');

        $timezone = $user
            ->preference
            ?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        $now = $reference === null
            ? CarbonImmutable::now($timezone)
            : CarbonImmutable::instance(
                $reference
            )->setTimezone($timezone);

        $today = $now
            ->startOfDay()
            ->toDateString();

        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereDate(
                'start_date',
                '<=',
                $today
            )
            ->where(
                function ($query) use ($today): void {
                    $query
                        ->whereNull('end_date')
                        ->orWhereDate(
                            'end_date',
                            '>=',
                            $today
                        );
                }
            )
            ->with([
                'financeCategory',

                'periods' => function ($query) use ($today): void {
                    $query
                        ->whereDate(
                            'period_start',
                            '<=',
                            $today
                        )
                        ->whereDate(
                            'period_end',
                            '>=',
                            $today
                        )
                        ->orderByDesc(
                            'period_start'
                        )
                        ->orderByDesc('id');
                },
            ])
            ->orderBy('id')
            ->get();

        $totalLimit = '0.00';
        $totalUsed = '0.00';
        $totalRemaining = '0.00';

        $items = $budgets->map(
            function (Budget $budget) use (
                &$totalLimit,
                &$totalUsed,
                &$totalRemaining
            ): array {
                $period = $budget
                    ->periods
                    ->first();

                $budgetAmount = $period?->budget_amount
                    ?? $budget->amount;

                $usedAmount = $period?->used_amount
                    ?? '0.00';

                $remainingAmount = $period?->remaining_amount
                    ?? $budgetAmount;

                $usagePercent = $period?->usage_percent
                    ?? '0.00';

                $alertLevel = $period !== null
                    ? $this
                        ->periodService
                        ->alertLevel($period)
                    : BudgetAlertLevel::Safe;

                $totalLimit = bcadd(
                    $totalLimit,
                    $budgetAmount,
                    2
                );

                $totalUsed = bcadd(
                    $totalUsed,
                    $usedAmount,
                    2
                );

                $totalRemaining = bcadd(
                    $totalRemaining,
                    $remainingAmount,
                    2
                );

                return [
                    'budget' => $budget,
                    'period' => $period,

                    'alert_level' => $alertLevel,

                    'budget_amount' => $budgetAmount,

                    'used_amount' => $usedAmount,

                    'remaining_amount' => $remainingAmount,

                    'usage_percent' => $usagePercent,

                    'progress_width' => min(
                        100,
                        max(
                            0,
                            (float) $usagePercent
                        )
                    ),
                ];
            }
        )->values();

        $attentionItems = $items
            ->filter(
                static fn (array $item): bool => $item['alert_level']
                    !== BudgetAlertLevel::Safe
            )
            ->sort(
                static function (
                    array $left,
                    array $right
                ): int {
                    $rank = static fn (
                        BudgetAlertLevel $level
                    ): int => match ($level) {
                        BudgetAlertLevel::Exceeded => 2,
                        BudgetAlertLevel::Warning => 1,
                        BudgetAlertLevel::Safe => 0,
                    };

                    $levelComparison = $rank(
                        $right['alert_level']
                    ) <=> $rank(
                        $left['alert_level']
                    );

                    if ($levelComparison !== 0) {
                        return $levelComparison;
                    }

                    return (float) $right[
                        'usage_percent'
                    ] <=> (float) $left[
                        'usage_percent'
                    ];
                }
            )
            ->values();

        return [
            'generated_at' => $now,

            'currency_code' => $user
                ->preference
                ?->currency_code
                ?? config(
                    'laras.defaults.currency_code',
                    'IDR'
                ),

            'items' => $items,

            'attention_items' => $attentionItems,

            'summary' => [
                'active' => $items->count(),

                'safe' => $items
                    ->where(
                        'alert_level',
                        BudgetAlertLevel::Safe
                    )
                    ->count(),

                'warning' => $items
                    ->where(
                        'alert_level',
                        BudgetAlertLevel::Warning
                    )
                    ->count(),

                'exceeded' => $items
                    ->where(
                        'alert_level',
                        BudgetAlertLevel::Exceeded
                    )
                    ->count(),

                'total_limit' => $totalLimit,
                'total_used' => $totalUsed,

                'total_remaining' => $totalRemaining,
            ],
        ];
    }
}
