<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\TransactionPostingService;
use Carbon\CarbonImmutable;
use Database\Seeders\FinanceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFinancialOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_current_month_financial_summary(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 12:00:00',
                'Asia/Jakarta'
            )
        );

        $user = $this->completedUser();

        $source = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
        ]);

        $destination = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'SeaBank',
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $incomeCategory = $user->financeCategories()
            ->where('name', 'Gaji')
            ->firstOrFail();

        $expenseCategory = $user->financeCategories()
            ->where('name', 'Makanan & Minuman')
            ->firstOrFail();

        $service = app(
            TransactionPostingService::class
        );

        $service->postIncome(
            user: $user,
            accountId: $source->id,
            categoryId: $incomeCategory->id,
            amount: '1000000',
            data: [
                'occurred_at' => now(),
                'description' => 'Pendapatan bulan ini',
            ]
        );

        $service->postExpense(
            user: $user,
            accountId: $source->id,
            categoryId: $expenseCategory->id,
            amount: '250000',
            data: [
                'occurred_at' => now(),
                'description' => 'Belanja bulan ini',
            ]
        );

        $service->postTransfer(
            user: $user,
            sourceAccountId: $source->id,
            destinationAccountId: $destination->id,
            amount: '100000',
            adminFee: '2500',
            data: [
                'occurred_at' => now(),
                'description' => 'Transfer tabungan',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas(
                'monthlyIncome',
                '1000000.00'
            )
            ->assertViewHas(
                'monthlyExpense',
                '252500.00'
            )
            ->assertViewHas(
                'netCashFlow',
                '747500.00'
            )
            ->assertSee('Pendapatan bulan ini')
            ->assertSee('Belanja bulan ini')
            ->assertSee('Transfer tabungan');
    }

    public function test_cancelled_transactions_are_excluded_from_monthly_summary(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 12:00:00',
                'Asia/Jakarta'
            )
        );

        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '100000.00',
            'cached_balance' => '100000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $category = $user->financeCategories()
            ->where('name', 'Makanan & Minuman')
            ->firstOrFail();

        $service = app(
            TransactionPostingService::class
        );

        $transaction = $service->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $category->id,
            amount: '25000',
            data: [
                'occurred_at' => now(),
            ]
        );

        $service->cancel(
            user: $user,
            transactionId: $transaction->id
        );

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'monthlyExpense',
                '0.00'
            );
    }

    public function test_previous_month_transactions_are_not_included(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 12:00:00',
                'Asia/Jakarta'
            )
        );

        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '1000000.00',
            'cached_balance' => '1000000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $category = $user->financeCategories()
            ->where('name', 'Gaji')
            ->firstOrFail();

        app(TransactionPostingService::class)
            ->postIncome(
                user: $user,
                accountId: $account->id,
                categoryId: $category->id,
                amount: '500000',
                data: [
                    'occurred_at' => now()
                        ->subMonth()
                        ->startOfMonth()
                        ->addDays(5),
                ]
            );

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'monthlyIncome',
                '0.00'
            );
    }

    public function test_dashboard_returns_category_distribution(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-15 12:00:00',
                'Asia/Jakarta'
            )
        );

        $user = $this->completedUser();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
        ]);

        $this->seed(FinanceCategorySeeder::class);

        $food = $user->financeCategories()
            ->where('name', 'Makanan & Minuman')
            ->firstOrFail();

        $transport = $user->financeCategories()
            ->where('name', 'Transportasi')
            ->firstOrFail();

        $service = app(
            TransactionPostingService::class
        );

        $service->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $food->id,
            amount: '20000',
            data: [
                'occurred_at' => now(),
            ]
        );

        $service->postExpense(
            user: $user,
            accountId: $account->id,
            categoryId: $transport->id,
            amount: '30000',
            data: [
                'occurred_at' => now(),
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas(
                'categoryBreakdown',
                function ($categories): bool {
                    return $categories->count() === 2
                        && $categories->first()['name']
                            === 'Transportasi'
                        && $categories->first()['amount']
                            === '30000.00';
                }
            )
            ->assertSee('Makanan &amp; Minuman', false)
            ->assertSee('Transportasi');
    }

    public function test_dashboard_limits_recent_transactions_to_six(): void
    {
        $user = $this->completedUser();

        for ($index = 1; $index <= 7; $index++) {
            Transaction::factory()
                ->posted()
                ->expense()
                ->create([
                    'user_id' => $user->id,
                    'description' =>
                        'Transaksi '.$index,
                    'occurred_at' => now()
                        ->subMinutes(7 - $index),
                ]);
        }

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'recentTransactions',
                fn ($transactions): bool =>
                    $transactions->count() === 6
            )
            ->assertSee('Transaksi 7')
            ->assertDontSee('Transaksi 1');
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
