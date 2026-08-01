<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SubscriptionBillingPosted extends Notification
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $billingId,
        public readonly int $transactionId,
        public readonly string $subscriptionName,
        public readonly string $amount,
        public readonly string $currencyCode,
        public readonly string $scheduledFor,
        public readonly string $accountName
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
        return 'subscription-billing-posted';
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
            'kind' => 'subscription_billing_posted',

            'title' =>
                'Tagihan langganan berhasil dicatat',

            'message' => sprintf(
                '%s sebesar %s %s telah dipotong dari saldo %s.',
                $this->subscriptionName,
                $this->currencyCode,
                $formattedAmount,
                $this->accountName
            ),

            'subscription_id' =>
                $this->subscriptionId,

            'subscription_billing_id' =>
                $this->billingId,

            'transaction_id' =>
                $this->transactionId,

            'subscription_name' =>
                $this->subscriptionName,

            'amount' => $this->amount,

            'currency_code' =>
                $this->currencyCode,

            'scheduled_for' =>
                $this->scheduledFor,

            'account_name' =>
                $this->accountName,

            'severity' => 'success',
            'icon' => 'circle-check',
        ];
    }
}
