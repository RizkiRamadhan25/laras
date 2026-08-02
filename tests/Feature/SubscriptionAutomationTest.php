<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\SubscriptionAutomationService;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_day_reminder_is_only_sent_once(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-07 09:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
        ] = $this->financialContext();

        $subscription = $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'started_on' => '2026-08-01',
                'next_billing_on' => '2026-08-10',
            ]
        );

        $automation = app(
            SubscriptionAutomationService::class
        );

        $automation->run();
        $automation->run();

        $notifications =
            $user->notifications()->get();

        $this->assertCount(
            1,
            $notifications
        );

        $notification =
            $notifications->first();

        $this->assertSame(
            'subscription-renewal-reminder',
            $notification->type
        );

        $this->assertSame(
            3,
            $notification->data[
                'days_before'
            ]
        );

        $billing =
            SubscriptionBilling::query()
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->firstOrFail();

        $this->assertSame(
            [
                3,
            ],
            $billing->metadata[
                'reminders_sent'
            ]
        );
    }

    public function test_due_subscription_posts_expense_and_advances_cycle(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-10 09:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
        ] = $this->financialContext(
            balance: '500000.00'
        );

        $subscription = $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'name' => 'Netflix',
                'provider' => 'Netflix',
                'amount' => '186000',
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
                'billing_time' => '08:00',
            ]
        );

        $summary = app(
            SubscriptionAutomationService::class
        )->run();

        $this->assertSame(
            1,
            $summary['billings_posted']
        );

        $this->assertSame(
            '314000.00',
            $account->fresh()->cached_balance
        );

        $billing =
            SubscriptionBilling::query()
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->whereDate(
                    'scheduled_for',
                    '2026-08-10'
                )
                ->firstOrFail();

        $this->assertSame(
            SubscriptionBillingStatus::Posted,
            $billing->status
        );

        $this->assertNotNull(
            $billing->transaction_id
        );

        $transaction =
            Transaction::query()
                ->findOrFail(
                    $billing->transaction_id
                );

        $this->assertSame(
            TransactionType::Expense,
            $transaction->type
        );

        $this->assertSame(
            TransactionSource::System,
            $transaction->source
        );

        $entry = $transaction
            ->entries()
            ->firstOrFail();

        $this->assertSame(
            '-186000.00',
            $entry->amount
        );

        $subscription->refresh();

        $this->assertSame(
            '2026-09-10',
            $subscription
                ->next_billing_on
                ->toDateString()
        );

        $this->assertSame(
            2,
            $subscription
                ->billings()
                ->count()
        );

        $this->assertSame(
            1,
            $user->notifications()
                ->where(
                    'type',
                    'subscription-billing-posted'
                )
                ->count()
        );
    }

    public function test_insufficient_balance_marks_billing_failed(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-10 09:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
        ] = $this->financialContext(
            balance: '50000.00'
        );

        $subscription = $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'name' => 'Netflix',
                'amount' => '186000',
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
            ]
        );

        $automation = app(
            SubscriptionAutomationService::class
        );

        $automation->run(
            date: '2026-08-10',
            force: true
        );

        /*
         * Dicoba lagi pada hari yang sama.
         * Notifikasi gagal tidak boleh bertambah.
         */
        $automation->run(
            date: '2026-08-10',
            force: true
        );

        $billing =
            SubscriptionBilling::query()
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->whereDate(
                    'scheduled_for',
                    '2026-08-10'
                )
                ->firstOrFail();

        $this->assertSame(
            SubscriptionBillingStatus::Failed,
            $billing->status
        );

        $this->assertNotNull(
            $billing->failure_reason
        );

        $this->assertNull(
            $billing->transaction_id
        );

        $this->assertSame(
            '50000.00',
            $account->fresh()->cached_balance
        );

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertSame(
            1,
            $user->notifications()
                ->where(
                    'type',
                    'subscription-billing-failed'
                )
                ->count()
        );

        $this->assertContains(
            '2026-08-10',
            $billing->metadata[
                'failure_notifications_sent'
            ]
        );
    }

    public function test_posted_billing_is_not_processed_twice(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-10 09:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
        ] = $this->financialContext();

        $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'amount' => '59000',
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
            ]
        );

        $automation = app(
            SubscriptionAutomationService::class
        );

        $automation->run();
        $automation->run();

        $this->assertDatabaseCount(
            'transactions',
            1
        );

        $this->assertSame(
            '441000.00',
            $account->fresh()->cached_balance
        );

        $this->assertSame(
            1,
            SubscriptionBilling::query()
                ->where(
                    'status',
                    SubscriptionBillingStatus::Posted->value
                )
                ->count()
        );
    }

    public function test_billing_waits_until_configured_time(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-10 07:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
        ] = $this->financialContext();

        $subscription = $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'amount' => '59000',
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
                'billing_time' => '08:00',
            ]
        );

        $automation = app(
            SubscriptionAutomationService::class
        );

        $automation->run();

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $billing =
            SubscriptionBilling::query()
                ->where(
                    'subscription_id',
                    $subscription->id
                )
                ->firstOrFail();

        $this->assertSame(
            SubscriptionBillingStatus::Scheduled,
            $billing->status
        );

        $automation->run(
            date: '2026-08-10',
            force: true
        );

        $this->assertDatabaseCount(
            'transactions',
            1
        );
    }

    public function test_paused_subscription_is_not_processed(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-10 09:00:00',
                'Asia/Jakarta'
            )
        );

        [
            $user,
            $account,
            $category,
        ] = $this->financialContext();

        $subscription = $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
            ]
        );

        app(SubscriptionService::class)
            ->pause(
                $user,
                $subscription->id
            );

        app(
            SubscriptionAutomationService::class
        )->run(
            date: '2026-08-10',
            force: true
        );

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertSame(
            SubscriptionStatus::Paused,
            $subscription->fresh()->status
        );
    }

    public function test_process_subscriptions_command_runs_successfully(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->financialContext();

        $this->subscriptionFor(
            user: $user,
            account: $account,
            category: $category,
            data: [
                'started_on' => '2026-08-10',
                'next_billing_on' => '2026-08-10',
                'billing_time' => '08:00',
            ]
        );

        $this
            ->artisan(
                'subscriptions:process',
                [
                    '--date' => '2026-08-10',
                    '--force' => true,
                ]
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'transactions',
            1
        );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Account,
     *     2: FinanceCategory
     * }
     */
    private function financialContext(
        string $balance = '500000.00'
    ): array {
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
            'currency_code' => 'IDR',
            'initial_balance' => $balance,
            'cached_balance' => $balance,
            'is_active' => true,
        ]);

        $category =
            FinanceCategory::factory()
                ->expense()
                ->create([
                    'user_id' => $user->id,

                    'flow_type' => FinanceFlowType::Expense,

                    'is_active' => true,
                ]);

        return [
            $user,
            $account,
            $category,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function subscriptionFor(
        User $user,
        Account $account,
        FinanceCategory $category,
        array $data = []
    ): Subscription {
        return app(
            SubscriptionService::class
        )->create(
            user: $user,
            data: array_merge(
                [
                    'account_id' => $account->id,

                    'finance_category_id' => $category->id,

                    'name' => 'Spotify',
                    'provider' => 'Spotify',
                    'amount' => '59000',

                    'interval_unit' => 'month',

                    'interval_count' => 1,

                    'started_on' => '2026-08-01',

                    'next_billing_on' => '2026-08-10',

                    'end_on' => null,
                    'billing_time' => '08:00',

                    'auto_post' => true,

                    'reminder_days' => [
                        3,
                        1,
                    ],
                ],
                $data
            )
        );
    }
}
