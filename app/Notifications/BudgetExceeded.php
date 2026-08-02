<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BudgetExceeded extends Notification
{
    public function __construct(
        public readonly int $budgetId,
        public readonly int $budgetPeriodId,
        public readonly string $budgetName,
        public readonly string $categoryName,
        public readonly string $usedAmount,
        public readonly string $budgetAmount,
        public readonly string $remainingAmount,
        public readonly string $usagePercent,
        public readonly string $currencyCode,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly string $deduplicationKey
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [
            'database',
        ];
    }

    public function databaseType(
        object $notifiable
    ): string {
        return 'budget-exceeded';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(
        object $notifiable
    ): array {
        $overAmount = bccomp(
            $this->remainingAmount,
            '0.00',
            2
        ) < 0
            ? bcsub(
                '0.00',
                $this->remainingAmount,
                2
            )
            : '0.00';

        return [
            'kind' => 'budget_exceeded',

            'title' => 'Batas anggaran terlampaui',

            'message' => sprintf(
                '%s telah melewati batas sebesar %s %s.',
                $this->budgetName,
                $this->currencyCode,
                number_format(
                    (float) $overAmount,
                    0,
                    ',',
                    '.'
                )
            ),

            'budget_id' => $this->budgetId,

            'budget_period_id' => $this->budgetPeriodId,

            'budget_name' => $this->budgetName,

            'category_name' => $this->categoryName,

            'used_amount' => $this->usedAmount,

            'budget_amount' => $this->budgetAmount,

            'remaining_amount' => $this->remainingAmount,

            'usage_percent' => $this->usagePercent,

            'currency_code' => $this->currencyCode,

            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,

            'deduplication_key' => $this->deduplicationKey,

            'severity' => 'danger',
            'icon' => 'circle-alert',
        ];
    }
}
