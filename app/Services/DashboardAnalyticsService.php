<?php

namespace App\Services;

use App\Enums\TransactionEntryRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class DashboardAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $user->loadMissing('preference');

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');

        $locale = $user->preference?->locale
            ?? config('laras.defaults.locale');

        $currencyCode = $user->preference?->currency_code
            ?? config('laras.defaults.currency_code');

        $now = CarbonImmutable::now($timezone)
            ->locale($locale);

        $monthStart = $now->startOfMonth();
        $monthEnd = $now->endOfMonth();

        /*
         * Input pengguna dan tampilan mengikuti zona waktu pengguna.
         * Query database mengikuti timezone aplikasi.
         */
        $databaseMonthStart = $monthStart->setTimezone(
            config('app.timezone')
        );

        $databaseMonthEnd = $monthEnd->setTimezone(
            config('app.timezone')
        );

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->get();

        $totalBalance = $accounts->reduce(
            static fn (
                string $total,
                Account $account
            ): string => bcadd(
                $total,
                $account->cached_balance,
                2
            ),
            '0.00'
        );

        $monthTransactions = $user->transactions()
            ->where(
                'status',
                TransactionStatus::Posted->value
            )
            ->whereBetween(
                'occurred_at',
                [
                    $databaseMonthStart,
                    $databaseMonthEnd,
                ]
            )
            ->with([
                'entries.financeCategory',
            ])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $summary = $this->summarizeMonth(
            transactions: $monthTransactions,
            timezone: $timezone,
            monthStart: $monthStart,
            now: $now,
        );

        $recentTransactions = $user->transactions()
            ->with([
                'entries.account',
                'entries.financeCategory',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return [
            'user' => $user,
            'accounts' => $accounts,
            'currencyCode' => $currencyCode,
            'timezone' => $timezone,

            'totalBalance' => $totalBalance,
            'monthlyIncome' => $summary['income'],
            'monthlyExpense' => $summary['expense'],
            'netCashFlow' => $summary['net_cash_flow'],

            'postedTransactionCount' =>
                $monthTransactions->count(),

            'cashFlowChart' =>
                $summary['cash_flow_chart'],

            'categoryBreakdown' =>
                $summary['category_breakdown'],

            'categoryChart' =>
                $summary['category_chart'],

            'recentTransactions' =>
                $recentTransactions,

            'greeting' => $greeting,

            'currentDate' => $now->translatedFormat(
                'l, d F Y'
            ),

            'monthLabel' => $now->translatedFormat(
                'F Y'
            ),
        ];
    }

    /**
     * @param EloquentCollection<int, Transaction> $transactions
     * @return array<string, mixed>
     */
    private function summarizeMonth(
        EloquentCollection $transactions,
        string $timezone,
        CarbonImmutable $monthStart,
        CarbonImmutable $now
    ): array {
        $dailyTotals = [];

        $cursor = $monthStart->startOfDay();
        $todayEnd = $now->endOfDay();

        while ($cursor->lessThanOrEqualTo($todayEnd)) {
            $dateKey = $cursor->format('Y-m-d');

            $dailyTotals[$dateKey] = [
                'label' => $cursor->translatedFormat('d M'),
                'income' => '0.00',
                'expense' => '0.00',
            ];

            $cursor = $cursor->addDay();
        }

        $monthlyIncome = '0.00';
        $monthlyExpense = '0.00';

        /**
         * @var array<string, array{
         *     name: string,
         *     color: string,
         *     amount: string
         * }> $categoryTotals
         */
        $categoryTotals = [];

        foreach ($transactions as $transaction) {
            $dateKey = $transaction->occurred_at
                ->timezone($timezone)
                ->format('Y-m-d');

            foreach ($transaction->entries as $entry) {
                if (
                    $this->isIncomeEntry(
                        $transaction,
                        $entry
                    )
                ) {
                    $monthlyIncome = bcadd(
                        $monthlyIncome,
                        $entry->amount,
                        2
                    );

                    if (isset($dailyTotals[$dateKey])) {
                        $dailyTotals[$dateKey]['income'] = bcadd(
                            $dailyTotals[$dateKey]['income'],
                            $entry->amount,
                            2
                        );
                    }

                    continue;
                }

                if (
                    ! $this->isExpenseEntry(
                        $transaction,
                        $entry
                    )
                ) {
                    continue;
                }

                $absoluteAmount = bcsub(
                    '0.00',
                    $entry->amount,
                    2
                );

                $monthlyExpense = bcadd(
                    $monthlyExpense,
                    $absoluteAmount,
                    2
                );

                if (isset($dailyTotals[$dateKey])) {
                    $dailyTotals[$dateKey]['expense'] = bcadd(
                        $dailyTotals[$dateKey]['expense'],
                        $absoluteAmount,
                        2
                    );
                }

                $category = $entry->financeCategory;

                $categoryKey = $category !== null
                    ? 'category-'.$category->id
                    : 'uncategorized';

                if (! isset($categoryTotals[$categoryKey])) {
                    $categoryTotals[$categoryKey] = [
                        'name' => $category?->name
                            ?? 'Tanpa kategori',

                        'color' => $category?->color
                            ?? '#64748B',

                        'amount' => '0.00',
                    ];
                }

                $categoryTotals[$categoryKey]['amount'] = bcadd(
                    $categoryTotals[$categoryKey]['amount'],
                    $absoluteAmount,
                    2
                );
            }
        }

        $netCashFlow = bcsub(
            $monthlyIncome,
            $monthlyExpense,
            2
        );

        $categoryBreakdown = collect($categoryTotals)
            ->sortByDesc(
                static fn (array $category): float =>
                    (float) $category['amount']
            )
            ->take(6)
            ->values();

        return [
            'income' => $monthlyIncome,
            'expense' => $monthlyExpense,
            'net_cash_flow' => $netCashFlow,

            'cash_flow_chart' => [
                'labels' => array_values(
                    array_column(
                        $dailyTotals,
                        'label'
                    )
                ),

                'income' => array_values(
                    array_map(
                        static fn (array $day): float =>
                            (float) $day['income'],
                        $dailyTotals
                    )
                ),

                'expense' => array_values(
                    array_map(
                        static fn (array $day): float =>
                            (float) $day['expense'],
                        $dailyTotals
                    )
                ),
            ],

            'category_breakdown' =>
                $categoryBreakdown,

            'category_chart' => [
                'labels' => $categoryBreakdown
                    ->pluck('name')
                    ->values()
                    ->all(),

                'values' => $categoryBreakdown
                    ->map(
                        static fn (array $category): float =>
                            (float) $category['amount']
                    )
                    ->values()
                    ->all(),

                'colors' => $categoryBreakdown
                    ->pluck('color')
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function isIncomeEntry(
        Transaction $transaction,
        TransactionEntry $entry
    ): bool {
        return $transaction->type
            === TransactionType::Income
            && $entry->role
                === TransactionEntryRole::Principal
            && bccomp(
                $entry->amount,
                '0.00',
                2
            ) > 0;
    }

    private function isExpenseEntry(
        Transaction $transaction,
        TransactionEntry $entry
    ): bool {
        if (
            bccomp(
                $entry->amount,
                '0.00',
                2
            ) >= 0
        ) {
            return false;
        }

        $principalExpense =
            $transaction->type
                === TransactionType::Expense
            && $entry->role
                === TransactionEntryRole::Principal;

        $transactionFee =
            $entry->role
                === TransactionEntryRole::Fee;

        return $principalExpense || $transactionFee;
    }
}
