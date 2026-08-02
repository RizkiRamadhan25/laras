<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetManagementService;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_budget_index(): void
    {
        $user = $this->user();

        $this
            ->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('Anggaran')
            ->assertSee('Tambah anggaran');
    }

    public function test_user_can_create_budget(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Makanan'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('budgets.store'),
                [
                    'finance_category_id' =>
                        $category->id,

                    'name' =>
                        'Anggaran Makanan',

                    'amount' =>
                        '1200000.00',

                    'period_type' =>
                        BudgetPeriodType::Monthly
                            ->value,

                    'warning_threshold_percent' =>
                        '80.00',

                    'start_date' =>
                        now()
                            ->startOfMonth()
                            ->toDateString(),
                ]
            );

        $budget =
            Budget::query()->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'budgets.show',
                    $budget
                )
            )
            ->assertSessionHas('status');

        $this->assertSame(
            $user->id,
            $budget->user_id
        );

        $this->assertSame(
            $category->id,
            $budget
                ->finance_category_id
        );

        $this->assertSame(
            '1200000.00',
            $budget->amount
        );
    }

    public function test_user_cannot_create_budget_with_other_users_category(): void
    {
        $user = $this->user();

        $otherUser = $this->user();

        $category = $this->category(
            $otherUser,
            'expense',
            'Transportasi'
        );

        $this
            ->actingAs($user)
            ->from(
                route('budgets.create')
            )
            ->post(
                route('budgets.store'),
                [
                    'finance_category_id' =>
                        $category->id,

                    'name' =>
                        'Anggaran Transportasi',

                    'amount' =>
                        '500000.00',

                    'period_type' =>
                        'monthly',

                    'warning_threshold_percent' =>
                        '80.00',

                    'start_date' =>
                        now()
                            ->startOfMonth()
                            ->toDateString(),
                ]
            )
            ->assertRedirect(
                route('budgets.create')
            )
            ->assertSessionHasErrors(
                'finance_category_id'
            );

        $this->assertDatabaseCount(
            'budgets',
            0
        );
    }

    public function test_user_can_update_budget_and_active_period(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Hiburan'
        );

        $budget = $this->budget(
            $user,
            $category
        );

        $this
            ->actingAs($user)
            ->put(
                route(
                    'budgets.update',
                    $budget
                ),
                [
                    'name' =>
                        'Anggaran Hiburan Baru',

                    'amount' =>
                        '1500000.00',

                    'warning_threshold_percent' =>
                        '75.00',
                ]
            )
            ->assertRedirect(
                route(
                    'budgets.show',
                    $budget
                )
            )
            ->assertSessionHas('status');

        $budget->refresh();

        $period = $budget
            ->periods()
            ->firstOrFail();

        $this->assertSame(
            'Anggaran Hiburan Baru',
            $budget->name
        );

        $this->assertSame(
            '1500000.00',
            $budget->amount
        );

        $this->assertSame(
            '1500000.00',
            $period->budget_amount
        );

        $this->assertSame(
            '1500000.00',
            $period->remaining_amount
        );
    }

    public function test_user_cannot_view_or_update_another_users_budget(): void
    {
        $user = $this->user();

        $otherUser = $this->user();

        $category = $this->category(
            $otherUser,
            'expense',
            'Belanja'
        );

        $budget = $this->budget(
            $otherUser,
            $category
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.show',
                    $budget
                )
            )
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->put(
                route(
                    'budgets.update',
                    $budget
                ),
                [
                    'name' =>
                        'Diubah pengguna lain',

                    'amount' =>
                        '100000.00',

                    'warning_threshold_percent' =>
                        '80.00',
                ]
            )
            ->assertForbidden();
    }

    public function test_user_can_deactivate_and_reactivate_budget(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Internet'
        );

        $budget = $this->budget(
            $user,
            $category
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'budgets.deactivate',
                    $budget
                )
            )
            ->assertRedirect(
                route(
                    'budgets.show',
                    $budget
                )
            );

        $this->assertFalse(
            $budget
                ->fresh()
                ->is_active
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'budgets.activate',
                    $budget
                )
            )
            ->assertRedirect(
                route(
                    'budgets.show',
                    $budget
                )
            );

        $this->assertTrue(
            $budget
                ->fresh()
                ->is_active
        );
    }

    public function test_inactive_budget_cannot_be_reactivated_when_category_has_another_active_budget(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Teknologi'
        );

        $firstBudget = $this->budget(
            $user,
            $category
        );

        app(
            BudgetManagementService::class
        )->deactivate(
            $user,
            $firstBudget
        );

        $secondBudget = app(
            BudgetService::class
        )->create(
            $user,
            $category,
            array_merge(
                $this->payload(),
                [
                    'name' =>
                        'Anggaran Teknologi Aktif',
                ]
            )
        );

        $this->assertTrue(
            $secondBudget->is_active
        );

        $this
            ->actingAs($user)
            ->from(
                route(
                    'budgets.show',
                    $firstBudget
                )
            )
            ->patch(
                route(
                    'budgets.activate',
                    $firstBudget
                )
            )
            ->assertRedirect(
                route(
                    'budgets.show',
                    $firstBudget
                )
            )
            ->assertSessionHasErrors(
                'finance_category_id'
            );

        $this->assertFalse(
            $firstBudget
                ->fresh()
                ->is_active
        );
    }

    public function test_budget_detail_displays_period_history(): void
    {
        $user = $this->user();

        $category = $this->category(
            $user,
            'expense',
            'Pendidikan'
        );

        $budget = $this->budget(
            $user,
            $category
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.show',
                    $budget
                )
            )
            ->assertOk()
            ->assertSee($budget->name)
            ->assertSee(
                'Riwayat periode'
            )
            ->assertSee(
                $category->name
            );
    }

    private function user(): User
    {
        $user = User::factory()->create([
            'onboarding_completed_at' =>
                now(),

            'is_active' => true,
        ]);

        UserPreference::query()
            ->updateOrCreate(
                [
                    'user_id' =>
                        $user->id,
                ],
                [
                    'locale' => 'id',
                    'currency_code' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'date_format' => 'd/m/Y',
                    'time_format' => 'H:i',
                    'week_starts_on' => 1,
                ]
            );

        return $user;
    }

    private function category(
        User $user,
        string $flowType,
        string $name
    ): FinanceCategory {
        $category =
            new FinanceCategory();

        $category->forceFill([
            'user_id' =>
                $user->id,

            'flow_type' =>
                $flowType,

            'name' =>
                $name,

            'icon' =>
                'wallet-cards',

            'color' =>
                '#2563EB',

            'is_system' =>
                false,

            'is_active' =>
                true,

            'sort_order' =>
                0,
        ]);

        $category->save();

        return $category;
    }

    private function budget(
        User $user,
        FinanceCategory $category
    ): Budget {
        return app(
            BudgetService::class
        )->create(
            $user,
            $category,
            $this->payload()
        );
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'name' =>
                'Anggaran Bulanan',

            'amount' =>
                '1000000.00',

            'period_type' =>
                BudgetPeriodType::Monthly
                    ->value,

            'warning_threshold_percent' =>
                '80.00',

            'start_date' =>
                now()
                    ->startOfMonth()
                    ->toDateString(),
        ];
    }
}
