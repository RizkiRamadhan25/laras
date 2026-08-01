<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Enums\SubscriptionIntervalUnit;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_has_expected_relationships_and_casts(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'started_on' => '2026-08-01',
                'next_billing_on' => '2026-09-01',

                'interval_unit' =>
                    SubscriptionIntervalUnit::Month,

                'status' =>
                    SubscriptionStatus::Active,
            ]);

        $this->assertTrue(
            $subscription->user->is($user)
        );

        $this->assertTrue(
            $subscription->account->is($account)
        );

        $this->assertTrue(
            $subscription
                ->financeCategory
                ->is($category)
        );

        $this->assertSame(
            SubscriptionIntervalUnit::Month,
            $subscription->interval_unit
        );

        $this->assertSame(
            SubscriptionStatus::Active,
            $subscription->status
        );

        $this->assertSame(
            '59000.00',
            $subscription->amount
        );

        $this->assertSame(
            [
                3,
                1,
            ],
            $subscription->reminder_days
        );
    }

    public function test_service_creates_subscription_and_initial_billing(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $subscription = app(
            SubscriptionService::class
        )->create(
            user: $user,
            data: [
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'name' => 'Netflix',
                'provider' => 'Netflix',

                'amount' => '186000',

                'interval_unit' => 'month',
                'interval_count' => 1,

                'started_on' => '2026-08-10',

                'next_billing_on' =>
                    '2026-08-10',

                'end_on' => null,
                'billing_time' => '08:00',

                'auto_post' => true,

                'reminder_days' => [
                    3,
                    1,
                ],
            ]
        );

        $this->assertSame(
            SubscriptionStatus::Active,
            $subscription->status
        );

        $this->assertSame(
            '186000.00',
            $subscription->amount
        );

        $this->assertSame(
            'IDR',
            $subscription->currency_code
        );

        $this->assertDatabaseHas(
            'subscription_billings',
            [
                'subscription_id' =>
                    $subscription->id,

                'user_id' => $user->id,

                'amount' => '186000.00',

                'status' =>
                    SubscriptionBillingStatus
                        ::Scheduled->value,
            ]
        );

        $billing = SubscriptionBilling::query()
            ->where(
                'subscription_id',
                $subscription->id
            )
            ->firstOrFail();

        $this->assertSame(
            '2026-08-10',
            $billing->scheduled_for->toDateString()
        );
    }

    public function test_subscription_must_use_expense_category(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $incomeCategory =
            FinanceCategory::factory()
                ->income()
                ->create([
                    'user_id' => $user->id,
                    'flow_type' =>
                        FinanceFlowType::Income,
                ]);

        $this->expectException(
            DomainException::class
        );

        $this->expectExceptionMessage(
            'Langganan harus menggunakan kategori pengeluaran.'
        );

        app(SubscriptionService::class)->create(
            user: $user,
            data: [
                'account_id' => $account->id,

                'finance_category_id' =>
                    $incomeCategory->id,

                'name' => 'Netflix',
                'amount' => '186000',
                'interval_unit' => 'month',
                'interval_count' => 1,
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
            ]
        );
    }

    public function test_user_cannot_use_another_users_account(): void
    {
        $user = $this->completedUser();

        $otherUser = $this->completedUser();

        $otherAccount =
            $this->accountFor($otherUser);

        $category = $this->expenseCategoryFor(
            $user
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(SubscriptionService::class)->create(
            user: $user,
            data: [
                'account_id' =>
                    $otherAccount->id,

                'finance_category_id' =>
                    $category->id,

                'name' => 'Netflix',
                'amount' => '186000',
                'interval_unit' => 'month',
                'interval_count' => 1,
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
            ]
        );
    }

    public function test_duplicate_billing_cycle_is_not_created(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $service = app(
            SubscriptionService::class
        );

        $subscription = $service->create(
            user: $user,
            data: [
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'name' => 'Spotify',
                'amount' => '54990',
                'interval_unit' => 'month',
                'interval_count' => 1,
                'started_on' => '2026-08-15',
                'next_billing_on' => '2026-08-15',
            ]
        );

        $service->scheduleCurrentBilling(
            $subscription
        );

        $service->scheduleCurrentBilling(
            $subscription
        );

        $this->assertSame(
            1,
            SubscriptionBilling::query()
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->whereDate(
                    'scheduled_for',
                    '2026-08-15'
                )
                ->count()
        );
    }

    public function test_monthly_billing_preserves_original_anchor_day(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'started_on' => '2026-01-31',

                'next_billing_on' =>
                    '2026-01-31',

                'interval_unit' =>
                    SubscriptionIntervalUnit::Month,

                'interval_count' => 1,
            ]);

        $service = app(
            SubscriptionService::class
        );

        $february = $service
            ->calculateNextBillingOn(
                subscription: $subscription,
                currentBillingOn: '2026-01-31'
            );

        $march = $service
            ->calculateNextBillingOn(
                subscription: $subscription,
                currentBillingOn: $february
            );

        $this->assertSame(
            '2026-02-28',
            $february->toDateString()
        );

        $this->assertSame(
            '2026-03-31',
            $march->toDateString()
        );
    }

    public function test_yearly_billing_handles_february_twenty_ninth(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $subscription = Subscription::factory()
            ->yearly()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'started_on' => '2024-02-29',

                'next_billing_on' =>
                    '2024-02-29',
            ]);

        $service = app(
            SubscriptionService::class
        );

        $next2025 = $service
            ->calculateNextBillingOn(
                subscription: $subscription,
                currentBillingOn: '2024-02-29'
            );

        $next2028 = $service
            ->calculateNextBillingOn(
                subscription: $subscription,
                currentBillingOn: '2027-02-28'
            );

        $this->assertSame(
            '2025-02-28',
            $next2025->toDateString()
        );

        $this->assertSame(
            '2028-02-29',
            $next2028->toDateString()
        );
    }

    public function test_subscription_can_be_paused_resumed_and_cancelled(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'status' =>
                    SubscriptionStatus::Active,
            ]);

        $service = app(
            SubscriptionService::class
        );

        $paused = $service->pause(
            $user,
            $subscription->id
        );

        $this->assertSame(
            SubscriptionStatus::Paused,
            $paused->status
        );

        $this->assertNotNull(
            $paused->paused_at
        );

        $resumed = $service->resume(
            $user,
            $subscription->id
        );

        $this->assertSame(
            SubscriptionStatus::Active,
            $resumed->status
        );

        $this->assertNull(
            $resumed->paused_at
        );

        $cancelled = $service->cancel(
            $user,
            $subscription->id
        );

        $this->assertSame(
            SubscriptionStatus::Cancelled,
            $cancelled->status
        );

        $this->assertNull(
            $cancelled->next_billing_on
        );

        $this->assertNotNull(
            $cancelled->cancelled_at
        );
    }

    public function test_successful_billing_advances_to_next_cycle(): void
    {
        $user = $this->completedUser();

        $account = $this->accountFor($user);

        $category = $this->expenseCategoryFor(
            $user
        );

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'started_on' => '2026-08-10',

                'next_billing_on' =>
                    '2026-08-10',

                'interval_unit' =>
                    SubscriptionIntervalUnit::Month,

                'interval_count' => 1,
                'end_on' => null,
            ]);

        app(SubscriptionService::class)
            ->scheduleCurrentBilling(
                $subscription
            );

        $advanced = app(
            SubscriptionService::class
        )->advanceAfterSuccessfulBilling(
            user: $user,
            subscriptionId: $subscription->id,
            billedFor: '2026-08-10'
        );

        $this->assertSame(
            '2026-08-10',
            $advanced->last_billed_on
                ->toDateString()
        );

        $this->assertSame(
            '2026-09-10',
            $advanced->next_billing_on
                ->toDateString()
        );

        $this->assertDatabaseHas(
            'subscription_billings',
            [
                'subscription_id' =>
                    $subscription->id,

                'status' =>
                    SubscriptionBillingStatus
                        ::Scheduled->value,
            ]
        );

        $nextBilling = SubscriptionBilling::query()
            ->where(
                'subscription_id',
                $subscription->id
            )
            ->whereDate(
                'scheduled_for',
                '2026-09-10'
            )
            ->firstOrFail();

        $this->assertSame(
            SubscriptionBillingStatus::Scheduled,
            $nextBilling->status
        );

        $this->assertSame(
            '2026-09-10',
            $nextBilling->scheduled_for->toDateString()
        );
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

    private function accountFor(
        User $user
    ): Account {
        return Account::factory()->create([
            'user_id' => $user->id,
            'currency_code' => 'IDR',
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
            'is_active' => true,
        ]);
    }

    private function expenseCategoryFor(
        User $user
    ): FinanceCategory {
        return FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,

                'flow_type' =>
                    FinanceFlowType::Expense,

                'is_active' => true,
            ]);
    }
}
