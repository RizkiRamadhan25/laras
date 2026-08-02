<?php

namespace Database\Factories;

use App\Enums\SubscriptionBillingStatus;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionBilling>
 */
class SubscriptionBillingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),

            'user_id' => User::factory(),
            'transaction_id' => null,

            'scheduled_for' => now()->addMonth()->toDateString(),

            'amount' => '59000.00',
            'currency_code' => 'IDR',

            'status' => SubscriptionBillingStatus::Scheduled,

            'attempted_at' => null,
            'processed_at' => null,
            'failure_reason' => null,
            'metadata' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionBillingStatus::Failed,

            'attempted_at' => now(),

            'failure_reason' => 'Saldo rekening tidak mencukupi.',
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionBillingStatus::Posted,

            'attempted_at' => now(),
            'processed_at' => now(),
        ]);
    }
}
