<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\TransactionEntry;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BudgetTransactionQueryService
{
    /**
     * @return LengthAwarePaginator<int, TransactionEntry>
     */
    public function paginateForPeriod(
        Budget $budget,
        BudgetPeriod $period,
        int $perPage = 10
    ): LengthAwarePaginator {
        $budget->loadMissing(
            'user.preference'
        );

        [$databaseStart, $databaseEnd] =
            $this->databaseBounds(
                $budget,
                CarbonImmutable::parse(
                    $period
                        ->period_start
                        ->toDateString()
                ),
                CarbonImmutable::parse(
                    $period
                        ->period_end
                        ->toDateString()
                )
            );

        return TransactionEntry::query()
            ->select('transaction_entries.*')
            ->join(
                'transactions',
                'transactions.id',
                '=',
                'transaction_entries.transaction_id'
            )
            ->with([
                'transaction',
                'account',
            ])
            ->where(
                'transactions.user_id',
                $budget->user_id
            )
            ->where(
                'transactions.status',
                TransactionStatus::Posted->value
            )
            ->whereNull(
                'transactions.deleted_at'
            )
            ->where(
                'transaction_entries.finance_category_id',
                $budget->finance_category_id
            )
            ->where(
                'transaction_entries.amount',
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
            ->orderByDesc(
                'transactions.occurred_at'
            )
            ->orderByDesc(
                'transaction_entries.id'
            )
            ->paginate(
                $perPage,
                ['transaction_entries.*'],
                'usage_page'
            )
            ->withQueryString();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function databaseBounds(
        Budget $budget,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd
    ): array {
        $timezone = $budget
            ->user
            ->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        $databaseTimezone = config(
            'app.timezone',
            'UTC'
        );

        $databaseStart = CarbonImmutable::parse(
            $periodStart->toDateString(),
            $timezone
        )
            ->startOfDay()
            ->setTimezone($databaseTimezone)
            ->format('Y-m-d H:i:s');

        $databaseEnd = CarbonImmutable::parse(
            $periodEnd->toDateString(),
            $timezone
        )
            ->endOfDay()
            ->setTimezone($databaseTimezone)
            ->format('Y-m-d H:i:s');

        return [
            $databaseStart,
            $databaseEnd,
        ];
    }
}
