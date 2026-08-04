<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModernFinanceFormMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_form_uses_modern_fields_and_currency_prefix(): void
    {
        $user = $this->completedUser();

        $this
            ->actingAs($user)
            ->get(route('accounts.create'))
            ->assertOk()
            ->assertSee('data-modern-account-form', false)
            ->assertSee('name="initial_balance"', false)
            ->assertSee('laras-field--prefix', false)
            ->assertSee('data-laras-field', false);
    }

    public function test_transaction_form_uses_modern_conditional_fields(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
        ]);

        FinanceCategory::factory()
            ->income()
            ->create([
                'user_id' => $user->id,
            ]);

        FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $this
            ->actingAs($user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('data-modern-transaction-form', false)
            ->assertSee('name="amount"', false)
            ->assertSee('name="occurred_at"', false)
            ->assertSee('laras-choice-card', false)
            ->assertSee('laras-field--always-floating', false);
    }

    public function test_budget_form_uses_modern_amount_threshold_and_date_fields(): void
    {
        $user = $this->completedUser();

        FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $this
            ->actingAs($user)
            ->get(route('budgets.create'))
            ->assertOk()
            ->assertSee('data-modern-budget-form', false)
            ->assertSee('name="warning_threshold_percent"', false)
            ->assertSee('laras-field--suffix', false)
            ->assertSee('name="start_date"', false);
    }

    public function test_subscription_form_uses_modern_cycle_and_schedule_fields(): void
    {
        $user = $this->completedUser();

        Account::factory()->create([
            'user_id' => $user->id,
        ]);

        FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $this
            ->actingAs($user)
            ->get(route('subscriptions.create'))
            ->assertOk()
            ->assertSee('data-modern-subscription-form', false)
            ->assertSee('name="interval_count"', false)
            ->assertSee('name="billing_time"', false)
            ->assertSee('name="next_billing_on"', false)
            ->assertSee('laras-field--always-floating', false);
    }

    private function completedUser(): User
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
            'is_active' => true,
        ]);

        UserPreference::query()->create([
            'user_id' => $user->id,
            'locale' => 'id',
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'week_starts_on' => 1,
        ]);

        return $user;
    }
}
