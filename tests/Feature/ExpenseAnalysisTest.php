<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\TransactionSource;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\ExpenseAnalysisService;
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_expense_analysis_page(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 12:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $foodCategory,
        ] = $this->context();

        $this->postExpense(
            user: $user,
            account: $account,
            category: $foodCategory,
            amount: '100000',
            occurredAt: '2026-08-10 10:00:00'
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'analysis.index',
                    ['period' => 'month']
                )
            )
            ->assertOk()
            ->assertSee(
                'Pahami pola pengeluaranmu.'
            )
            ->assertSee('Makanan')
            ->assertSee('100.000');
    }

    public function test_service_calculates_week_month_and_year_per_category(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-15 12:00:00',
            'Asia/Jakarta'
        );

        $this->travelTo($reference);

        [
            $user,
            $account,
            $foodCategory,
        ] = $this->context();

        $transportCategory =
            FinanceCategory::factory()
                ->expense()
                ->create([
                    'user_id' => $user->id,
                    'name' => 'Transportasi',

                    'flow_type' =>
                        FinanceFlowType::Expense,

                    'is_active' => true,
                ]);

        /*
         * Masuk minggu, bulan, dan tahun.
         */
        $this->postExpense(
            user: $user,
            account: $account,
            category: $foodCategory,
            amount: '100000',
            occurredAt: '2026-08-15 10:00:00'
        );

        /*
         * Masuk bulan dan tahun,
         * tetapi tidak masuk tujuh hari terakhir.
         */
        $this->postExpense(
            user: $user,
            account: $account,
            category: $transportCategory,
            amount: '200000',
            occurredAt: '2026-08-01 10:00:00'
        );

        /*
         * Hanya masuk tahun berjalan.
         */
        $this->postExpense(
            user: $user,
            account: $account,
            category: $foodCategory,
            amount: '300000',
            occurredAt: '2026-01-10 10:00:00'
        );

        $analysis = app(
            ExpenseAnalysisService::class
        )->build(
            user: $user,
            selectedPeriod: 'month',
            reference: $reference
        );

        $food = collect(
            $analysis['categories']
        )->firstWhere(
            'name',
            'Makanan'
        );

        $transport = collect(
            $analysis['categories']
        )->firstWhere(
            'name',
            'Transportasi'
        );

        $this->assertNotNull($food);
        $this->assertNotNull($transport);

        $this->assertSame(
            '100000.00',
            $food['week']
        );

        $this->assertSame(
            '100000.00',
            $food['month']
        );

        $this->assertSame(
            '400000.00',
            $food['year']
        );

        $this->assertSame(
            '0.00',
            $transport['week']
        );

        $this->assertSame(
            '200000.00',
            $transport['month']
        );

        $this->assertSame(
            '200000.00',
            $transport['year']
        );

        $this->assertSame(
            '300000.00',
            $analysis['summary'][
                'selected_total'
            ]
        );

        $this->assertSame(
            '600000.00',
            $analysis['summary'][
                'year_total'
            ]
        );
    }

    public function test_cancelled_transactions_are_not_counted(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-15 12:00:00',
            'Asia/Jakarta'
        );

        [
            $user,
            $account,
            $foodCategory,
        ] = $this->context();

        $this->postExpense(
            user: $user,
            account: $account,
            category: $foodCategory,
            amount: '100000',
            occurredAt: '2026-08-15 10:00:00'
        );

        $cancelled = $this->postExpense(
            user: $user,
            account: $account,
            category: $foodCategory,
            amount: '50000',
            occurredAt: '2026-08-14 10:00:00'
        );

        $cancelled->forceFill([
            'status' => 'cancelled',
        ])->save();

        $analysis = app(
            ExpenseAnalysisService::class
        )->build(
            user: $user,
            selectedPeriod: 'week',
            reference: $reference
        );

        $this->assertSame(
            '100000.00',
            $analysis['summary'][
                'selected_total'
            ]
        );
    }

    public function test_analysis_does_not_include_another_users_expenses(): void
    {
        $reference = CarbonImmutable::parse(
            '2026-08-15 12:00:00',
            'Asia/Jakarta'
        );

        [
            $user,
            $account,
            $foodCategory,
        ] = $this->context();

        [
            $otherUser,
            $otherAccount,
            $otherCategory,
        ] = $this->context();

        $this->postExpense(
            user: $user,
            account: $account,
            category: $foodCategory,
            amount: '100000',
            occurredAt: '2026-08-15 10:00:00'
        );

        $this->postExpense(
            user: $otherUser,
            account: $otherAccount,
            category: $otherCategory,
            amount: '900000',
            occurredAt: '2026-08-15 10:00:00'
        );

        $analysis = app(
            ExpenseAnalysisService::class
        )->build(
            user: $user,
            selectedPeriod: 'month',
            reference: $reference
        );

        $this->assertSame(
            '100000.00',
            $analysis['summary'][
                'selected_total'
            ]
        );
    }

    public function test_period_filter_is_validated(): void
    {
        [$user] = $this->context();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'analysis.index',
                    ['period' => 'invalid']
                )
            )
            ->assertSessionHasErrors(
                'period'
            );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Account,
     *     2: FinanceCategory
     * }
     */
    private function context(): array
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

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'currency_code' => 'IDR',
            'initial_balance' =>
                '5000000.00',

            'cached_balance' =>
                '5000000.00',

            'is_active' => true,
        ]);

        $category =
            FinanceCategory::factory()
                ->expense()
                ->create([
                    'user_id' => $user->id,
                    'name' => 'Makanan',

                    'flow_type' =>
                        FinanceFlowType::Expense,

                    'is_active' => true,
                ]);

        return [
            $user,
            $account,
            $category,
        ];
    }

    private function postExpense(
        User $user,
        Account $account,
        FinanceCategory $category,
        string $amount,
        string $occurredAt
    ): Transaction {
        return app(
            TransactionPostingService::class
        )->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: $amount,

            data: [
                'source' =>
                    TransactionSource::System,

                'occurred_at' =>
                    CarbonImmutable::parse(
                        $occurredAt,
                        'Asia/Jakarta'
                    ),

                'description' =>
                    'Pengeluaran pengujian',

                'counterparty' => null,
                'reference_number' => null,
                'notes' => null,
            ]
        );
    }
}
