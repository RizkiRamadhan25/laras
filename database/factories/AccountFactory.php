<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement([
                'BCA Utama',
                'Mandiri',
                'SeaBank',
                'Dompet Tunai',
            ]),
            'type' => AccountType::Bank,
            'institution' => fake()->randomElement([
                'BCA',
                'Mandiri',
                'BNI',
                'SeaBank',
            ]),
            'currency_code' => 'IDR',
            'initial_balance' => '0.00',
            'cached_balance' => '0.00',
            'account_number_last_four' => fake()->numerify('####'),
            'color' => '#2563EB',
            'icon' => 'landmark',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Uang Tunai',
            'type' => AccountType::Cash,
            'institution' => null,
            'account_number_last_four' => null,
            'icon' => 'wallet',
        ]);
    }

    public function eWallet(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Dompet Digital',
            'type' => AccountType::EWallet,
            'institution' => null,
            'account_number_last_four' => null,
            'icon' => 'smartphone',
        ]);
    }
}
