<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BudgetUsageSyncService
{
    public function __construct(
        private readonly BudgetPeriodService $periodService
    ) {
    }

    public function syncForTransaction(
        Transaction $transaction
    ): void {
        $transaction->loadMissing([
            'user.preference',
            'entries',
        ]);

        $categoryIds = $transaction
            ->entries
            ->filter(
                static fn ($entry): bool =>
                    $entry->finance_category_id !== null
                    && bccomp(
                        $entry->amount,
                        '0.00',
                        2
                    ) < 0
            )
            ->pluck('finance_category_id')
            ->map(
                static fn (mixed $categoryId): int =>
                    (int) $categoryId
            )
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return;
        }

        $timezone = $this->userTimezone(
            $transaction->user
        );

        $localDate = CarbonImmutable::instance(
            $transaction->occurred_at
        )
            ->setTimezone($timezone)
            ->startOfDay();

        foreach ($categoryIds as $categoryId) {
            $this->syncActiveBudgetsForCategoryDate(
                $transaction->user,
                $categoryId,
                $localDate
            );
        }
    }

    public function syncActiveBudgetsForCategoryDate(
        User $user,
        int $categoryId,
        CarbonInterface $localDate
    ): void {
        $date = CarbonImmutable::parse(
            $localDate->toDateString()
        );

        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->where(
                'finance_category_id',
                $categoryId
            )
            ->where('is_active', true)
            ->whereDate(
                'start_date',
                '<=',
                $date->toDateString()
            )
            ->where(
                function ($query) use ($date): void {
                    $query
                        ->whereNull('end_date')
                        ->orWhereDate(
                            'end_date',
                            '>=',
                            $date->toDateString()
                        );
                }
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($budgets as $budget) {
            $this->syncBudgetForDate(
                $budget,
                $date
            );
        }
    }

    public function syncBudgetForDate(
        Budget $budget,
        CarbonInterface $localDate
    ): ?BudgetPeriod {
        $date = CarbonImmutable::parse(
            $localDate->toDateString()
        );

        if (! $budget->isActiveOn($date)) {
            return null;
        }

        [$periodStart, $periodEnd] =
            $this->periodService->periodBounds(
                $budget,
                $date
            );

        $usedAmount = $this->usedAmount(
            $budget,
            $periodStart,
            $periodEnd
        );

        return $this->periodService->sync(
            $budget,
            $usedAmount,
            $date
        );
    }

    public function syncExistingPeriods(
        Budget $budget
    ): void {
        if (! $budget->is_active) {
            return;
        }

        $budget->loadMissing([
            'user.preference',
            'periods',
        ]);

        foreach ($budget->periods as $period) {
            $usedAmount = $this->usedAmount(
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

            $this->periodService->sync(
                $budget,
                $usedAmount,
                $period->period_start
            );
        }
    }

    /**
     * Menyinkronkan semua periode yang sudah ada, periode
     * yang memiliki transaksi, dan periode saat ini.
     *
     * @return int jumlah periode unik yang disinkronkan
     */
    public function syncAllRelevantPeriods(
        Budget $budget
    ): int {
        if (! $budget->is_active) {
            return 0;
        }

        $budget->loadMissing([
            'user.preference',
            'periods',
        ]);

        $timezone = $this->userTimezone(
            $budget->user
        );

        /** @var array<string, CarbonImmutable> $referenceDates */
        $referenceDates = [];

        $rememberDate = function (
            CarbonImmutable $localDate
        ) use (
            $budget,
            &$referenceDates
        ): void {
            if (! $budget->isActiveOn($localDate)) {
                return;
            }

            [$periodStart, $periodEnd] =
                $this->periodService->periodBounds(
                    $budget,
                    $localDate
                );

            $key = $periodStart->toDateString()
                .'|'
                .$periodEnd->toDateString();

            $referenceDates[$key] = $localDate;
        };

        foreach ($budget->periods as $period) {
            $rememberDate(
                CarbonImmutable::parse(
                    $period
                        ->period_start
                        ->toDateString()
                )
            );
        }

        Transaction::query()
            ->where(
                'user_id',
                $budget->user_id
            )
            ->where(
                'status',
                TransactionStatus::Posted->value
            )
            ->whereHas(
                'entries',
                function ($query) use ($budget): void {
                    $query
                        ->where(
                            'finance_category_id',
                            $budget->finance_category_id
                        )
                        ->where(
                            'amount',
                            '<',
                            0
                        );
                }
            )
            ->select([
                'id',
                'occurred_at',
            ])
            ->orderBy('id')
            ->chunkById(
                500,
                function ($transactions) use (
                    $timezone,
                    $rememberDate
                ): void {
                    foreach ($transactions as $transaction) {
                        $localDate = CarbonImmutable::instance(
                            $transaction->occurred_at
                        )
                            ->setTimezone($timezone)
                            ->startOfDay();

                        $rememberDate($localDate);
                    }
                }
            );

        $today = CarbonImmutable::now(
            $timezone
        )->startOfDay();

        $rememberDate($today);

        ksort($referenceDates);

        foreach ($referenceDates as $referenceDate) {
            $this->syncBudgetForDate(
                $budget,
                $referenceDate
            );
        }

        return count($referenceDates);
    }

    private function usedAmount(
        Budget $budget,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd
    ): string {
        $budget->loadMissing(
            'user.preference'
        );

        [$databaseStart, $databaseEnd] =
            $this->databaseBounds(
                $budget,
                $periodStart,
                $periodEnd
            );

        $signedTotal = DB::table(
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
                'entries.finance_category_id',
                $budget->finance_category_id
            )
            ->where('entries.amount', '<', 0)
            ->whereBetween(
                'transactions.occurred_at',
                [
                    $databaseStart,
                    $databaseEnd,
                ]
            )
            ->sum('entries.amount');

        $normalizedTotal = bcadd(
            (string) $signedTotal,
            '0.00',
            2
        );

        return bccomp(
            $normalizedTotal,
            '0.00',
            2
        ) < 0
            ? bcsub(
                '0.00',
                $normalizedTotal,
                2
            )
            : '0.00';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function databaseBounds(
        Budget $budget,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd
    ): array {
        $timezone = $this->userTimezone(
            $budget->user
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

    private function userTimezone(
        User $user
    ): string {
        $user->loadMissing('preference');

        return $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    }
}
