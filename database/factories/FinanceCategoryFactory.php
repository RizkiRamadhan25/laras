<?php

namespace Database\Factories;

use App\Enums\FinanceFlowType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\FinanceCategory>
 */
class FinanceCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement([
                'Makanan',
                'Transportasi',
                'Gaji',
                'Belanja',
            ]),
            'flow_type' => FinanceFlowType::Expense,
            'icon' => 'circle-dollar-sign',
            'color' => '#2563EB',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (): array => [
            'flow_type' => FinanceFlowType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (): array => [
            'flow_type' => FinanceFlowType::Expense,
        ]);
    }
}
