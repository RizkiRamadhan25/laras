<?php

namespace Database\Factories;

use App\Enums\TransactionEntryRole;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TransactionEntry>
 */
class TransactionEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'account_id' => Account::factory(),
            'finance_category_id' => null,
            'amount' => '-10000.00',
            'role' => TransactionEntryRole::Principal,
            'memo' => null,
        ];
    }

    public function inflow(): static
    {
        return $this->state(fn (): array => [
            'amount' => '10000.00',
        ]);
    }

    public function outflow(): static
    {
        return $this->state(fn (): array => [
            'amount' => '-10000.00',
        ]);
    }
}
