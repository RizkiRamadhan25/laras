<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SubscriptionBillingFailed extends Notification
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $billingId,
        public readonly string $subscriptionName,
        public readonly string $amount,
        public readonly string $currencyCode,
        public readonly string $scheduledFor,
        public readonly string $accountName,
        public readonly string $failureReason
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
        return 'subscription-billing-failed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(
        object $notifiable
    ): array {
        $formattedAmount = number_format(
            (float) $this->amount,
            0,
            ',',
            '.'
        );

        return [
            'kind' => 'subscription_billing_failed',

            'title' => 'Tagihan langganan gagal dicatat',

            'message' => sprintf(
                '%s sebesar %s %s gagal dicatat dari rekening %s. %s',
                $this->subscriptionName,
                $this->currencyCode,
                $formattedAmount,
                $this->accountName,
                $this->failureReason
            ),

            'subscription_id' => $this->subscriptionId,

            'subscription_billing_id' => $this->billingId,

            'subscription_name' => $this->subscriptionName,

            'amount' => $this->amount,

            'currency_code' => $this->currencyCode,

            'scheduled_for' => $this->scheduledFor,

            'account_name' => $this->accountName,

            'failure_reason' => $this->failureReason,

            'severity' => 'danger',
            'icon' => 'circle-alert',
        ];
    }
}
