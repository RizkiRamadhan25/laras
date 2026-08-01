<?php

namespace App\Services;

use App\Enums\FinanceFlowType;
use App\Models\FinanceCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseAnalysisService
{
    /**
     * @return array<string, mixed>
     */
    public function build(
        User $user,
        string $selectedPeriod = 'month',
        ?DateTimeInterface $reference = null
    ): array {
        $timezone = $this->userTimezone(
            $user
        );

        $locale = $user->preference()
            ->value('locale')
            ?? config(
                'laras.defaults.locale',
                'id'
            );

        $now = $reference === null
            ? CarbonImmutable::now($timezone)
            : CarbonImmutable::instance(
                $reference
            )->setTimezone($timezone);

        $periods = $this->periodDefinitions(
            $now
        );

        if (
            ! array_key_exists(
                $selectedPeriod,
                $periods
            )
        ) {
            $selectedPeriod = 'month';
        }

        /*
         * Awal periode tahun sebelumnya merupakan
         * batas paling awal yang dibutuhkan untuk:
         *
         * - perbandingan tahun;
         * - analisis tahun berjalan;
         * - tren 12 bulan.
         */
        $queryStart =
            $periods['year']['previous_start'];

        $queryEnd = $now->endOfDay();

        $rows = $this->expenseRows(
            user: $user,
            start: $queryStart,
            end: $queryEnd,
            timezone: $timezone
        );

        $categories = FinanceCategory::query()
            ->withTrashed()
            ->where(
                'user_id',
                $user->id
            )
            ->whereIn(
                'flow_type',
                [
                    FinanceFlowType::Expense->value,
                    FinanceFlowType::Both->value,
                ]
            )
            ->orderBy('name')
            ->get();

        /**
         * @var array<int, array<string, mixed>>
         */
        $categoryData = [];

        foreach ($categories as $category) {
            $categoryData[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,

                'archived' =>
                    $category->trashed(),

                'week' => '0.00',
                'month' => '0.00',
                'year' => '0.00',

                'previous_selected' => '0.00',
            ];
        }

        $monthlyTrend =
            $this->emptyMonthlyTrend(
                now: $now,
                locale: $locale
            );

        foreach ($rows as $row) {
            $categoryId = (int) (
                $row->finance_category_id
            );

            if (
                ! array_key_exists(
                    $categoryId,
                    $categoryData
                )
            ) {
                continue;
            }

            $occurredAt = CarbonImmutable::parse(
                $row->occurred_at,
                config(
                    'app.timezone',
                    'UTC'
                )
            )->setTimezone($timezone);

            $amount = $this->positiveAmount(
                $row->amount
            );

            foreach (
                [
                    'week',
                    'month',
                    'year',
                ] as $periodKey
            ) {
                if (
                    $this->isWithin(
                        $occurredAt,
                        $periods[$periodKey][
                            'current_start'
                        ],
                        $periods[$periodKey][
                            'current_end'
                        ]
                    )
                ) {
                    $categoryData[
                        $categoryId
                    ][$periodKey] = bcadd(
                        $categoryData[
                            $categoryId
                        ][$periodKey],
                        $amount,
                        2
                    );
                }
            }

            if (
                $this->isWithin(
                    $occurredAt,
                    $periods[
                        $selectedPeriod
                    ]['previous_start'],
                    $periods[
                        $selectedPeriod
                    ]['previous_end']
                )
            ) {
                $categoryData[
                    $categoryId
                ]['previous_selected'] = bcadd(
                    $categoryData[
                        $categoryId
                    ]['previous_selected'],
                    $amount,
                    2
                );
            }

            $monthKey = $occurredAt->format(
                'Y-m'
            );

            if (
                array_key_exists(
                    $monthKey,
                    $monthlyTrend
                )
            ) {
                $monthlyTrend[
                    $monthKey
                ]['total'] = bcadd(
                    $monthlyTrend[
                        $monthKey
                    ]['total'],
                    $amount,
                    2
                );
            }
        }

        $totals = [
            'week' => '0.00',
            'month' => '0.00',
            'year' => '0.00',
            'previous_selected' => '0.00',
        ];

        foreach ($categoryData as $category) {
            $totals['week'] = bcadd(
                $totals['week'],
                $category['week'],
                2
            );

            $totals['month'] = bcadd(
                $totals['month'],
                $category['month'],
                2
            );

            $totals['year'] = bcadd(
                $totals['year'],
                $category['year'],
                2
            );

            $totals[
                'previous_selected'
            ] = bcadd(
                $totals[
                    'previous_selected'
                ],
                $category[
                    'previous_selected'
                ],
                2
            );
        }

        $selectedTotal =
            $totals[$selectedPeriod];

        foreach (
            $categoryData
            as $categoryId => $category
        ) {
            $selectedAmount =
                $category[$selectedPeriod];

            $share = bccomp(
                $selectedTotal,
                '0.00',
                2
            ) > 0
                ? round(
                    (
                        (float) $selectedAmount
                        / (float) $selectedTotal
                    ) * 100,
                    1
                )
                : 0.0;

            $change = $this->percentageChange(
                current: $selectedAmount,
                previous:
                    $category[
                        'previous_selected'
                    ]
            );

            $categoryData[$categoryId][
                'selected'
            ] = $selectedAmount;

            $categoryData[$categoryId][
                'share'
            ] = $share;

            $categoryData[$categoryId][
                'change_percent'
            ] = $change['percentage'];

            $categoryData[$categoryId][
                'trend'
            ] = $change['trend'];
        }

        $categoryData = array_values(
            $categoryData
        );

        usort(
            $categoryData,
            function (
                array $left,
                array $right
            ): int {
                $amountComparison =
                    (float) $right['selected']
                    <=>
                    (float) $left['selected'];

                if ($amountComparison !== 0) {
                    return $amountComparison;
                }

                return strcmp(
                    $left['name'],
                    $right['name']
                );
            }
        );

        $topCategory = collect(
            $categoryData
        )->first(
            fn (array $category): bool =>
                bccomp(
                    $category['selected'],
                    '0.00',
                    2
                ) > 0
        );

        $currentRange =
            $periods[$selectedPeriod];

        $daysInSelectedPeriod = max(
            1,
            $currentRange['current_start']
                ->startOfDay()
                ->diffInDays(
                    $currentRange[
                        'current_end'
                    ]->startOfDay()
                ) + 1
        );

        $averageDaily = bcdiv(
            $selectedTotal,
            (string) $daysInSelectedPeriod,
            2
        );

        $overallChange =
            $this->percentageChange(
                current: $selectedTotal,
                previous:
                    $totals[
                        'previous_selected'
                    ]
            );

        return [
            'selected_period' =>
                $selectedPeriod,

            'periods' => $periods,

            'generated_at' => $now,

            'summary' => [
                'selected_total' =>
                    $selectedTotal,

                'previous_total' =>
                    $totals[
                        'previous_selected'
                    ],

                'week_total' =>
                    $totals['week'],

                'month_total' =>
                    $totals['month'],

                'year_total' =>
                    $totals['year'],

                'average_daily' =>
                    $averageDaily,

                'days_count' =>
                    $daysInSelectedPeriod,

                'change_percent' =>
                    $overallChange[
                        'percentage'
                    ],

                'trend' =>
                    $overallChange['trend'],

                'top_category' =>
                    $topCategory,
            ],

            'categories' =>
                $categoryData,

            'monthly_trend' =>
                array_values($monthlyTrend),

            'chart_data' => [
                'categories' => [
                    'labels' => collect(
                        $categoryData
                    )
                        ->filter(
                            fn (
                                array $category
                            ): bool =>
                                bccomp(
                                    $category[
                                        'selected'
                                    ],
                                    '0.00',
                                    2
                                ) > 0
                        )
                        ->pluck('name')
                        ->values()
                        ->all(),

                    'values' => collect(
                        $categoryData
                    )
                        ->filter(
                            fn (
                                array $category
                            ): bool =>
                                bccomp(
                                    $category[
                                        'selected'
                                    ],
                                    '0.00',
                                    2
                                ) > 0
                        )
                        ->map(
                            fn (
                                array $category
                            ): float =>
                                (float) $category[
                                    'selected'
                                ]
                        )
                        ->values()
                        ->all(),
                ],

                'monthly' => [
                    'labels' => collect(
                        $monthlyTrend
                    )
                        ->pluck('label')
                        ->values()
                        ->all(),

                    'values' => collect(
                        $monthlyTrend
                    )
                        ->map(
                            fn (
                                array $month
                            ): float =>
                                (float) $month[
                                    'total'
                                ]
                        )
                        ->values()
                        ->all(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function periodDefinitions(
        CarbonImmutable $now
    ): array {
        $weekStart = $now
            ->startOfDay()
            ->subDays(6);

        $weekEnd = $now->endOfDay();

        $previousWeekStart =
            $weekStart->subDays(7);

        $previousWeekEnd =
            $weekStart->subSecond();

        $monthStart =
            $now->startOfMonth();

        $monthEnd = $now->endOfDay();

        $previousMonthStart = $monthStart
            ->subMonthNoOverflow()
            ->startOfMonth();

        $previousMonthElapsedDay = min(
            $now->day,
            $previousMonthStart->daysInMonth
        );

        $previousMonthEnd =
            $previousMonthStart
                ->addDays(
                    $previousMonthElapsedDay - 1
                )
                ->endOfDay();

        $yearStart = $now->startOfYear();
        $yearEnd = $now->endOfDay();

        $previousYearStart =
            $yearStart->subYear();

        $previousYearTargetMonth =
            CarbonImmutable::create(
                $now->year - 1,
                $now->month,
                1,
                0,
                0,
                0,
                $now->timezone
            );

        $previousYearTargetDay = min(
            $now->day,
            $previousYearTargetMonth
                ->daysInMonth
        );

        $previousYearEnd =
            $previousYearTargetMonth
                ->day($previousYearTargetDay)
                ->endOfDay();

        return [
            'week' => [
                'label' =>
                    '7 hari terakhir',

                'short_label' =>
                    '7 Hari',

                'previous_label' =>
                    '7 hari sebelumnya',

                'current_start' =>
                    $weekStart,

                'current_end' =>
                    $weekEnd,

                'previous_start' =>
                    $previousWeekStart,

                'previous_end' =>
                    $previousWeekEnd,
            ],

            'month' => [
                'label' =>
                    'Bulan berjalan',

                'short_label' =>
                    'Bulan',

                'previous_label' =>
                    'Periode setara bulan lalu',

                'current_start' =>
                    $monthStart,

                'current_end' =>
                    $monthEnd,

                'previous_start' =>
                    $previousMonthStart,

                'previous_end' =>
                    $previousMonthEnd,
            ],

            'year' => [
                'label' =>
                    'Tahun berjalan',

                'short_label' =>
                    'Tahun',

                'previous_label' =>
                    'Periode setara tahun lalu',

                'current_start' =>
                    $yearStart,

                'current_end' =>
                    $yearEnd,

                'previous_start' =>
                    $previousYearStart,

                'previous_end' =>
                    $previousYearEnd,
            ],
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function expenseRows(
        User $user,
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $timezone
    ): Collection {
        $databaseTimezone = config(
            'app.timezone',
            'UTC'
        );

        $databaseStart = $start
            ->setTimezone($databaseTimezone)
            ->format('Y-m-d H:i:s');

        $databaseEnd = $end
            ->setTimezone($databaseTimezone)
            ->format('Y-m-d H:i:s');

        return DB::table(
            'transaction_entries as entries'
        )
            ->join(
                'transactions as transactions',
                'transactions.id',
                '=',
                'entries.transaction_id'
            )
            ->where(
                'transactions.user_id',
                $user->id
            )
            ->where(
                'transactions.status',
                'posted'
            )
            ->whereNull(
                'transactions.deleted_at'
            )
            ->whereNotNull(
                'entries.finance_category_id'
            )
            ->where(
                'entries.amount',
                '<',
                0
            )
            ->whereBetween(
                'transactions.occurred_at',
                [
                    $databaseStart,
                    $databaseEnd,
                ]
            )
            ->select([
                'entries.finance_category_id',
                'entries.amount',
                'transactions.occurred_at',
            ])
            ->orderBy(
                'transactions.occurred_at'
            )
            ->get();
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     total: string
     * }>
     */
    private function emptyMonthlyTrend(
        CarbonImmutable $now,
        string $locale
    ): array {
        $trend = [];

        $cursor = $now
            ->startOfMonth()
            ->subMonths(11);

        for ($month = 0; $month < 12; $month++) {
            $key = $cursor->format('Y-m');

            $trend[$key] = [
                'label' => $cursor
                    ->locale($locale)
                    ->translatedFormat('M Y'),

                'total' => '0.00',
            ];

            $cursor = $cursor->addMonth();
        }

        return $trend;
    }

    private function positiveAmount(
        mixed $amount
    ): string {
        $normalized = trim(
            (string) $amount
        );

        if (
            str_starts_with(
                $normalized,
                '-'
            )
        ) {
            $normalized = substr(
                $normalized,
                1
            );
        }

        return bcadd(
            $normalized,
            '0',
            2
        );
    }

    private function isWithin(
        CarbonImmutable $date,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): bool {
        return $date
            ->greaterThanOrEqualTo($start)
            && $date
                ->lessThanOrEqualTo($end);
    }

    /**
     * @return array{
     *     percentage: float|null,
     *     trend: string
     * }
     */
    private function percentageChange(
        string $current,
        string $previous
    ): array {
        if (
            bccomp(
                $previous,
                '0.00',
                2
            ) === 0
        ) {
            if (
                bccomp(
                    $current,
                    '0.00',
                    2
                ) === 0
            ) {
                return [
                    'percentage' => 0.0,
                    'trend' => 'same',
                ];
            }

            return [
                'percentage' => null,
                'trend' => 'new',
            ];
        }

        $percentage = round(
            (
                (
                    (float) $current
                    - (float) $previous
                )
                / (float) $previous
            ) * 100,
            1
        );

        $trend = match (true) {
            $percentage > 0 => 'up',
            $percentage < 0 => 'down',
            default => 'same',
        };

        return [
            'percentage' => $percentage,
            'trend' => $trend,
        ];
    }

    private function userTimezone(
        User $user
    ): string {
        return $user->preference()
            ->value('timezone')
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    }
}
