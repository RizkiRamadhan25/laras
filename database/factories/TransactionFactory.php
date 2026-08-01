<?php

namespace Database\Factories;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => TransactionType::Expense,
            'status' => TransactionStatus::Draft,
            'source' => TransactionSource::Manual,
            'occurred_at' => now(),
            'description' => fake()->sentence(3),
            'counterparty' => null,
            'reference_number' => null,
            'notes' => null,
            'metadata' => null,
            'posted_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn (): array => [
            'status' => TransactionStatus::Posted,
            'posted_at' => now(),
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Expense,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Transfer,
        ]);
    }
}
