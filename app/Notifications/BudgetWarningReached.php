<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BudgetWarningReached extends Notification
{
    public function __construct(
        public readonly int $budgetId,
        public readonly int $budgetPeriodId,
        public readonly string $budgetName,
        public readonly string $categoryName,
        public readonly string $usedAmount,
        public readonly string $budgetAmount,
        public readonly string $usagePercent,
        public readonly string $warningThresholdPercent,
        public readonly string $currencyCode,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly string $deduplicationKey
    ) {
    }

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
        return 'budget-warning-reached';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(
        object $notifiable
    ): array {
        return [
            'kind' => 'budget_warning',

            'title' =>
                'Anggaran mendekati batas',

            'message' => sprintf(
                '%s telah menggunakan %s%% dari batas %s %s.',
                $this->budgetName,
                number_format(
                    (float) $this->usagePercent,
                    2,
                    ',',
                    '.'
                ),
                $this->currencyCode,
                number_format(
                    (float) $this->budgetAmount,
                    0,
                    ',',
                    '.'
                )
            ),

            'budget_id' => $this->budgetId,

            'budget_period_id' =>
                $this->budgetPeriodId,

            'budget_name' => $this->budgetName,

            'category_name' =>
                $this->categoryName,

            'used_amount' => $this->usedAmount,

            'budget_amount' =>
                $this->budgetAmount,

            'usage_percent' =>
                $this->usagePercent,

            'warning_threshold_percent' =>
                $this->warningThresholdPercent,

            'currency_code' =>
                $this->currencyCode,

            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,

            'deduplication_key' =>
                $this->deduplicationKey,

            'severity' => 'warning',
            'icon' => 'triangle-alert',
        ];
    }
}
