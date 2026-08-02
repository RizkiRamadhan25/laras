<?php

namespace App\Services;

use App\Enums\BudgetAlertLevel;
use App\Enums\BudgetPeriodStatus;
use App\Enums\BudgetPeriodType;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Database\UniqueConstraintViolationException;

class BudgetPeriodService
{
    public function sync(
        Budget $budget,
        int|string $usedAmount = '0.00',
        ?CarbonInterface $referenceDate = null
    ): BudgetPeriod {
        $reference = $referenceDate
            ? CarbonImmutable::parse(
                $referenceDate->toDateString()
            )
            : CarbonImmutable::now(
                $this->budgetTimezone($budget)
            )->startOfDay();

        if (! $budget->isActiveOn($reference)) {
            throw new InvalidArgumentException(
                'Tanggal berada di luar masa aktif anggaran.'
            );
        }

        [$periodStart, $periodEnd] =
            $this->periodBounds(
                $budget,
                $reference
            );

        $budgetAmount = $this->toHundredths(
            $budget->amount
        );

        $used = $this->toHundredths(
            $usedAmount
        );

        if ($used < 0) {
            throw new InvalidArgumentException(
                'Penggunaan anggaran tidak boleh negatif.'
            );
        }

        if ($budgetAmount <= 0) {
            throw new InvalidArgumentException(
                'Batas anggaran harus lebih dari nol.'
            );
        }

        $remaining = $budgetAmount - $used;

        $usageBasisPoints =
            $this->usageBasisPoints(
                $used,
                $budgetAmount
            );

        $values = [
            'budget_amount' =>
                $this->formatHundredths(
                    $budgetAmount
                ),

            'used_amount' =>
                $this->formatHundredths(
                    $used
                ),

            'remaining_amount' =>
                $this->formatHundredths(
                    $remaining
                ),

            'usage_percent' =>
                $this->formatHundredths(
                    $usageBasisPoints
                ),

            'status' =>
                $this->periodStatus(
                    $periodStart,
                    $periodEnd,
                    $this->budgetTimezone(
                        $budget
                    )
                ),
        ];

        return DB::transaction(
            function () use (
                $budget,
                $periodStart,
                $periodEnd,
                $values
            ): BudgetPeriod {
                /*
                * Cari menggunakan whereDate agar kompatibel
                * dengan penyimpanan tanggal MySQL dan SQLite.
                */
                $period = BudgetPeriod::query()
                    ->where(
                        'budget_id',
                        $budget->id
                    )
                    ->whereDate(
                        'period_start',
                        $periodStart->toDateString()
                    )
                    ->whereDate(
                        'period_end',
                        $periodEnd->toDateString()
                    )
                    ->lockForUpdate()
                    ->first();

                if ($period === null) {
                    try {
                        $period = BudgetPeriod::query()
                            ->create([
                                'budget_id' =>
                                    $budget->id,

                                'period_start' =>
                                    $periodStart,

                                'period_end' =>
                                    $periodEnd,

                                ...$values,
                            ]);
                    } catch (
                        UniqueConstraintViolationException
                    ) {
                        /*
                        * Proses lain mungkin telah membuat
                        * periode yang sama lebih dahulu.
                        */
                        $period = BudgetPeriod::query()
                            ->where(
                                'budget_id',
                                $budget->id
                            )
                            ->whereDate(
                                'period_start',
                                $periodStart
                                    ->toDateString()
                            )
                            ->whereDate(
                                'period_end',
                                $periodEnd
                                    ->toDateString()
                            )
                            ->lockForUpdate()
                            ->firstOrFail();
                    }
                }

                $lockedPeriod = BudgetPeriod::query()
                    ->whereKey($period->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedPeriod
                    ->forceFill($values)
                    ->save();

                return $lockedPeriod->refresh();
            },
            3
        );
    }

    public function refreshExisting(
        BudgetPeriod $period
    ): BudgetPeriod {
        return DB::transaction(
            function () use ($period): BudgetPeriod {
                $lockedPeriod = BudgetPeriod::query()
                    ->whereKey($period->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedPeriod->loadMissing(
                    'budget'
                );

                $budgetAmount =
                    $this->toHundredths(
                        $lockedPeriod
                            ->budget
                            ->amount
                    );

                $usedAmount =
                    $this->toHundredths(
                        $lockedPeriod
                            ->used_amount
                    );

                if ($budgetAmount <= 0) {
                    throw new InvalidArgumentException(
                        'Batas anggaran harus lebih dari nol.'
                    );
                }

                if ($usedAmount < 0) {
                    throw new InvalidArgumentException(
                        'Penggunaan anggaran tidak boleh negatif.'
                    );
                }

                $remainingAmount =
                    $budgetAmount - $usedAmount;

                $usageBasisPoints =
                    $this->usageBasisPoints(
                        $usedAmount,
                        $budgetAmount
                    );

                $periodStart = CarbonImmutable::parse(
                    $lockedPeriod
                        ->period_start
                        ->toDateString()
                );

                $periodEnd = CarbonImmutable::parse(
                    $lockedPeriod
                        ->period_end
                        ->toDateString()
                );

                $lockedPeriod->forceFill([
                    'budget_amount' =>
                        $this->formatHundredths(
                            $budgetAmount
                        ),

                    'remaining_amount' =>
                        $this->formatHundredths(
                            $remainingAmount
                        ),

                    'usage_percent' =>
                        $this->formatHundredths(
                            $usageBasisPoints
                        ),

                    'status' =>
                        $this->periodStatus(
                            $periodStart,
                            $periodEnd,
                            $this->budgetTimezone(
                                $lockedPeriod->budget
                            )
                        ),
                ])->save();

                return $lockedPeriod->refresh();
            },
            3
        );
    }

    public function alertLevel(
        BudgetPeriod $period
    ): BudgetAlertLevel {
        $period->loadMissing(
            'budget'
        );

        $usedAmount =
            $this->toHundredths(
                $period->used_amount
            );

        $budgetAmount =
            $this->toHundredths(
                $period->budget_amount
            );

        $thresholdBasisPoints =
            $this->toHundredths(
                $period
                    ->budget
                    ->warning_threshold_percent
            );

        if ($usedAmount >= $budgetAmount) {
            return BudgetAlertLevel::Exceeded;
        }

        /*
        * Bandingkan nominal aslinya agar
        * 79,999999% tidak dianggap 80%
        * hanya karena pembulatan tampilan.
        */
        if (
            ($usedAmount * 10000)
            >= ($budgetAmount * $thresholdBasisPoints)
        ) {
            return BudgetAlertLevel::Warning;
        }

        return BudgetAlertLevel::Safe;
    }

    /**
     * @return array{
     *     0: CarbonImmutable,
     *     1: CarbonImmutable
     * }
     */
    public function periodBounds(
        Budget $budget,
        CarbonInterface $referenceDate
    ): array {
        $reference = CarbonImmutable::parse(
            $referenceDate->toDateString()
        );

        if (
            $budget->period_type
            === BudgetPeriodType::Custom
        ) {
            if ($budget->end_date === null) {
                throw new InvalidArgumentException(
                    'Periode khusus wajib memiliki tanggal selesai.'
                );
            }

            return [
                CarbonImmutable::parse(
                    $budget
                        ->start_date
                        ->toDateString()
                ),

                CarbonImmutable::parse(
                    $budget
                        ->end_date
                        ->toDateString()
                ),
            ];
        }

        $start =
            $reference->startOfMonth();

        $end =
            $reference->endOfMonth();

        $budgetStart =
            CarbonImmutable::parse(
                $budget
                    ->start_date
                    ->toDateString()
            );

        if ($start->lt($budgetStart)) {
            $start = $budgetStart;
        }

        if ($budget->end_date !== null) {
            $budgetEnd =
                CarbonImmutable::parse(
                    $budget
                        ->end_date
                        ->toDateString()
                );

            if ($end->gt($budgetEnd)) {
                $end = $budgetEnd;
            }
        }

        return [
            $start,
            $end,
        ];
    }

    private function periodStatus(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $timezone
    ): BudgetPeriodStatus {
        $todayDate = CarbonImmutable::now()
            ->setTimezone($timezone)
            ->toDateString();

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        if ($todayDate < $startDate) {
            return BudgetPeriodStatus::Upcoming;
        }

        if ($todayDate > $endDate) {
            return BudgetPeriodStatus::Closed;
        }

        return BudgetPeriodStatus::Active;
    }


    private function budgetTimezone(
        Budget $budget
    ): string {
        $budget->loadMissing(
            'user.preference'
        );

        return $budget
            ->user
            ?->preference
            ?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    }

    private function usageBasisPoints(
        int $usedAmount,
        int $budgetAmount
    ): int {
        /*
         * 10000 basis point =
         * 100,00 persen.
         *
         * Perhitungan menggunakan integer,
         * bukan float.
         */
        return intdiv(
            ($usedAmount * 10000)
            + intdiv(
                $budgetAmount,
                2
            ),
            $budgetAmount
        );
    }

    private function toHundredths(
        int|string $value
    ): int {
        $raw = trim(
            (string) $value
        );

        if (
            ! preg_match(
                '/^-?\d+(?:\.\d{1,2})?$/',
                $raw
            )
        ) {
            throw new InvalidArgumentException(
                'Nilai desimal tidak valid.'
            );
        }

        $isNegative =
            str_starts_with(
                $raw,
                '-'
            );

        if ($isNegative) {
            $raw = substr(
                $raw,
                1
            );
        }

        [
            $whole,
            $fraction,
        ] = array_pad(
            explode(
                '.',
                $raw,
                2
            ),
            2,
            ''
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        $amount =
            ((int) $whole * 100)
            + (int) substr(
                $fraction,
                0,
                2
            );

        return $isNegative
            ? -$amount
            : $amount;
    }

    private function formatHundredths(
        int $value
    ): string {
        $isNegative = $value < 0;

        $absolute = abs($value);

        $whole = intdiv(
            $absolute,
            100
        );

        $fraction = $absolute % 100;

        return sprintf(
            '%s%d.%02d',
            $isNegative ? '-' : '',
            $whole,
            $fraction
        );
    }
}
