<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SubscriptionRenewalReminder extends Notification
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $billingId,
        public readonly string $subscriptionName,
        public readonly string $amount,
        public readonly string $currencyCode,
        public readonly string $scheduledFor,
        public readonly int $daysBefore,
        public readonly string $accountName
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
        return 'subscription-renewal-reminder';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(
        object $notifiable
    ): array {
        $title = $this->daysBefore === 1
            ? 'Tagihan langganan besok'
            : sprintf(
                'Tagihan langganan dalam %d hari',
                $this->daysBefore
            );

        $formattedAmount = number_format(
            (float) $this->amount,
            0,
            ',',
            '.'
        );

        return [
            'kind' => 'subscription_reminder',
            'title' => $title,

            'message' => sprintf(
                '%s sebesar %s %s akan ditagihkan melalui %s.',
                $this->subscriptionName,
                $this->currencyCode,
                $formattedAmount,
                $this->accountName
            ),

            'subscription_id' => $this->subscriptionId,

            'subscription_billing_id' => $this->billingId,

            'subscription_name' => $this->subscriptionName,

            'amount' => $this->amount,

            'currency_code' => $this->currencyCode,

            'scheduled_for' => $this->scheduledFor,

            'days_before' => $this->daysBefore,

            'account_name' => $this->accountName,

            'severity' => 'warning',
            'icon' => 'bell-ring',
        ];
    }
}
