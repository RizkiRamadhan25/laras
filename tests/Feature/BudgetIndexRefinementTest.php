<?php

namespace Tests\Feature;

use App\Enums\BudgetPeriodType;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\BudgetManagementService;
use App\Services\BudgetPeriodService;
use App\Services\BudgetService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetIndexRefinementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 10:00:00'
            )
        );
    }

    public function test_user_can_search_budget_by_name_or_category(): void
    {
        $user = $this->user();

        $food = $this->budget(
            $user,
            'Makanan',
            'Belanja Makan Bulanan'
        );

        $transport = $this->budget(
            $user,
            'Transportasi',
            'Ongkos Harian'
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['q' => 'Makanan']
                )
            )
            ->assertOk()
            ->assertSee($food->name)
            ->assertDontSee(
                $transport->name
            );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['q' => 'Ongkos']
                )
            )
            ->assertOk()
            ->assertSee(
                $transport->name
            )
            ->assertDontSee($food->name);
    }

    public function test_user_can_filter_active_and_inactive_budgets(): void
    {
        $user = $this->user();

        $active = $this->budget(
            $user,
            'Makanan',
            'Anggaran Aktif'
        );

        $inactive = $this->budget(
            $user,
            'Hiburan',
            'Anggaran Nonaktif'
        );

        app(
            BudgetManagementService::class
        )->deactivate(
            $user,
            $inactive
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['status' => 'active']
                )
            )
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee(
                $inactive->name
            );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['status' => 'inactive']
                )
            )
            ->assertOk()
            ->assertSee($inactive->name)
            ->assertDontSee($active->name);
    }

    public function test_user_can_filter_budget_condition(): void
    {
        $user = $this->user();

        $safe = $this->budget(
            $user,
            'Makanan',
            'Anggaran Aman'
        );

        $warning = $this->budget(
            $user,
            'Transportasi',
            'Anggaran Peringatan'
        );

        $exceeded = $this->budget(
            $user,
            'Hiburan',
            'Anggaran Terlampaui'
        );

        $this->sync(
            $safe,
            '100000.00'
        );

        $this->sync(
            $warning,
            '800000.00'
        );

        $this->sync(
            $exceeded,
            '1100000.00'
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['condition' => 'warning']
                )
            )
            ->assertOk()
            ->assertSee($warning->name)
            ->assertDontSee($safe->name)
            ->assertDontSee(
                $exceeded->name
            );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['condition' => 'exceeded']
                )
            )
            ->assertOk()
            ->assertSee($exceeded->name)
            ->assertDontSee($safe->name)
            ->assertDontSee(
                $warning->name
            );
    }

    public function test_priority_sort_places_exceeded_then_warning_then_safe(): void
    {
        $user = $this->user();

        $safe = $this->budget(
            $user,
            'Makanan',
            'C Aman'
        );

        $warning = $this->budget(
            $user,
            'Transportasi',
            'B Peringatan'
        );

        $exceeded = $this->budget(
            $user,
            'Hiburan',
            'A Terlampaui'
        );

        $this->sync(
            $safe,
            '100000.00'
        );

        $this->sync(
            $warning,
            '800000.00'
        );

        $this->sync(
            $exceeded,
            '1100000.00'
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['sort' => 'priority']
                )
            )
            ->assertOk()
            ->assertSeeInOrder([
                $exceeded->name,
                $warning->name,
                $safe->name,
            ]);
    }

    public function test_invalid_filter_values_fall_back_to_safe_defaults(): void
    {
        $user = $this->user();

        $budget = $this->budget(
            $user,
            'Makanan',
            'Anggaran Normal'
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    [
                        'status' => 'invalid',
                        'condition' => [
                            'invalid',
                        ],
                        'sort' => 'invalid',
                    ]
                )
            )
            ->assertOk()
            ->assertSee($budget->name);
    }

    public function test_filtered_empty_state_is_different_from_first_budget_empty_state(): void
    {
        $user = $this->user();

        $this->budget(
            $user,
            'Makanan',
            'Anggaran Makanan'
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'budgets.index',
                    ['q' => 'Tidak Ditemukan']
                )
            )
            ->assertOk()
            ->assertSee(
                'Tidak ada anggaran yang cocok'
            )
            ->assertDontSee(
                'Belum ada anggaran'
            );
    }

    public function test_budget_progress_has_accessible_progressbar_information(): void
    {
        $user = $this->user();

        $budget = $this->budget(
            $user,
            'Makanan',
            'Anggaran Aksesibel'
        );

        $this->sync(
            $budget,
            '500000.00'
        );

        $this
            ->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertSee(
                'role="progressbar"',
                false
            )
            ->assertSee(
                'aria-valuenow="50"',
                false
            )
            ->assertSee(
                'aria-label="Penggunaan Anggaran Aksesibel"',
                false
            );
    }

    public function test_summary_counts_budgets_that_need_attention(): void
    {
        $user = $this->user();

        $safe = $this->budget(
            $user,
            'Makanan',
            'Anggaran Aman'
        );

        $warning = $this->budget(
            $user,
            'Transportasi',
            'Anggaran Peringatan'
        );

        $exceeded = $this->budget(
            $user,
            'Hiburan',
            'Anggaran Terlampaui'
        );

        $this->sync(
            $safe,
            '100000.00'
        );

        $this->sync(
            $warning,
            '800000.00'
        );

        $this->sync(
            $exceeded,
            '1100000.00'
        );

        $this
            ->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('Perlu perhatian')
            ->assertSeeInOrder([
                'Perlu perhatian',
                '2',
            ]);
    }

    private function user(): User
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),

            'is_active' => true,
        ]);

        UserPreference::query()
            ->updateOrCreate(
                [
                    'user_id' => $user->id,
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

    private function budget(
        User $user,
        string $categoryName,
        string $budgetName
    ): Budget {
        $category = new FinanceCategory;

        $category->forceFill([
            'user_id' => $user->id,
            'flow_type' => 'expense',
            'name' => $categoryName,
            'icon' => 'wallet-cards',
            'color' => '#2563EB',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $category->save();

        return app(
            BudgetService::class
        )->create(
            $user,
            $category,
            [
                'name' => $budgetName,
                'amount' => '1000000.00',
                'period_type' => BudgetPeriodType::Monthly
                    ->value,
                'warning_threshold_percent' => '80.00',
                'start_date' => '2026-08-01',
            ]
        );
    }

    private function sync(
        Budget $budget,
        string $usedAmount
    ): void {
        app(
            BudgetPeriodService::class
        )->sync(
            $budget,
            $usedAmount,
            CarbonImmutable::parse(
                '2026-08-15'
            )
        );
    }
}
