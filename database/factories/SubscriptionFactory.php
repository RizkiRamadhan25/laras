<?php

namespace Database\Factories;

use App\Enums\SubscriptionIntervalUnit;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),

            'finance_category_id' =>
                FinanceCategory::factory()
                    ->expense(),

            'name' => fake()->randomElement([
                'Netflix',
                'Spotify',
                'YouTube Membership',
                'Google One',
            ]),

            'provider' => null,
            'amount' => '59000.00',
            'currency_code' => 'IDR',

            'interval_unit' =>
                SubscriptionIntervalUnit::Month,

            'interval_count' => 1,

            'started_on' => now()->toDateString(),

            'next_billing_on' =>
                now()->addMonth()->toDateString(),

            'end_on' => null,
            'billing_time' => '08:00:00',
            'auto_post' => true,

            'reminder_days' => [
                3,
                1,
            ],

            'status' => SubscriptionStatus::Active,
            'last_billed_on' => null,
            'paused_at' => null,
            'cancelled_at' => null,
            'metadata' => null,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (): array => [
            'interval_unit' =>
                SubscriptionIntervalUnit::Year,

            'interval_count' => 1,
        ]);
    }

    public function manualPosting(): static
    {
        return $this->state(fn (): array => [
            'auto_post' => false,
        ]);
    }
}