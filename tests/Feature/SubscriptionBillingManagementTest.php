<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\SubscriptionBillingFailed;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_owned_billing_detail(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = $this->subscriptionFor(
            $user,
            $account,
            $category
        );

        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'subscriptions.billings.show',
                    [
                        'subscription' => $subscription->id,

                        'billing' => $billing->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Detail billing')
            ->assertSee($subscription->name);
    }

    public function test_user_cannot_view_another_users_billing(): void
    {
        [$user] = $this->context();

        [
            $otherUser,
            $otherAccount,
            $otherCategory,
        ] = $this->context();

        $subscription = $this->subscriptionFor(
            $otherUser,
            $otherAccount,
            $otherCategory
        );

        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'subscriptions.billings.show',
                    [
                        'subscription' => $subscription->id,

                        'billing' => $billing->id,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_failed_billing_can_be_retried_successfully(): void
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
        ] = $this->context(
            balance: '500000.00'
        );

        $subscription = $this->subscriptionFor(
            $user,
            $account,
            $category
        );

        $billing = $this->markBillingFailed(
            $subscription
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'subscriptions.billings.retry',
                    [
                        'subscription' => $subscription->id,

                        'billing' => $billing->id,
                    ]
                )
            )
            ->assertRedirectToRoute(
                'subscriptions.billings.show',
                [
                    'subscription' => $subscription->id,

                    'billing' => $billing->id,
                ]
            )
            ->assertSessionHas('status');

        $billing->refresh();

        $this->assertSame(
            SubscriptionBillingStatus::Posted,
            $billing->status
        );

        $this->assertNotNull(
            $billing->transaction_id
        );

        $this->assertSame(
            '441000.00',
            $account->fresh()->cached_balance
        );

        $this->assertDatabaseCount(
            'transactions',
            1
        );

        $this->assertSame(
            '2026-09-10',
            $subscription
                ->fresh()
                ->next_billing_on
                ->toDateString()
        );
    }

    public function test_failed_billing_remains_failed_when_balance_is_insufficient(): void
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
        ] = $this->context(
            balance: '10000.00'
        );

        $subscription = $this->subscriptionFor(
            $user,
            $account,
            $category
        );

        $billing = $this->markBillingFailed(
            $subscription
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'subscriptions.billings.retry',
                    [
                        'subscription' => $subscription->id,

                        'billing' => $billing->id,
                    ]
                )
            )
            ->assertRedirect()
            ->assertSessionHas('warning');

        $billing->refresh();

        $this->assertSame(
            SubscriptionBillingStatus::Failed,
            $billing->status
        );

        $this->assertNull(
            $billing->transaction_id
        );

        $this->assertSame(
            '10000.00',
            $account->fresh()->cached_balance
        );

        $this->assertDatabaseCount(
            'transactions',
            0
        );
    }

    public function test_posted_billing_cannot_be_retried(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = $this->subscriptionFor(
            $user,
            $account,
            $category
        );

        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $billing->forceFill([
            'status' => SubscriptionBillingStatus::Posted,

            'processed_at' => now(),
        ])->save();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'subscriptions.billings.retry',
                    [
                        'subscription' => $subscription->id,

                        'billing' => $billing->id,
                    ]
                )
            )
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseCount(
            'transactions',
            0
        );
    }

    public function test_failed_notification_opens_billing_detail(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = $this->subscriptionFor(
            $user,
            $account,
            $category
        );

        $billing = $this->markBillingFailed(
            $subscription
        );

        $user->notify(
            new SubscriptionBillingFailed(
                subscriptionId: $subscription->id,

                billingId: $billing->id,

                subscriptionName: $subscription->name,

                amount: $billing->amount,

                currencyCode: $billing->currency_code,

                scheduledFor: $billing
                    ->scheduled_for
                    ->toDateString(),

                accountName: $account->name,

                failureReason: $billing->failure_reason
            )
        );

        $notification = $user
            ->unreadNotifications()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'notifications.open',
                    $notification->id
                )
            )
            ->assertRedirectToRoute(
                'subscriptions.billings.show',
                [
                    'subscription' => $subscription->id,

                    'billing' => $billing->id,
                ]
            );
    }

    /**
     * @return array{
     *     0: User,
     *     1: Account,
     *     2: FinanceCategory
     * }
     */
    private function context(
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
            'name' => 'BCA Utama',
            'currency_code' => 'IDR',
            'initial_balance' => $balance,
            'cached_balance' => $balance,
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()
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

    private function subscriptionFor(
        User $user,
        Account $account,
        FinanceCategory $category
    ): Subscription {
        return app(
            SubscriptionService::class
        )->create(
            user: $user,
            data: [
                'account_id' => $account->id,

                'finance_category_id' => $category->id,

                'name' => 'Spotify',
                'provider' => 'Spotify',
                'amount' => '59000',

                'interval_unit' => 'month',
                'interval_count' => 1,

                'started_on' => '2026-08-10',

                'next_billing_on' => '2026-08-10',

                'end_on' => null,
                'billing_time' => '08:00',
                'auto_post' => true,

                'reminder_days' => [
                    3,
                    1,
                ],
            ]
        );
    }

    private function markBillingFailed(
        Subscription $subscription
    ): SubscriptionBilling {
        $billing = $subscription
            ->billings()
            ->firstOrFail();

        $billing->forceFill([
            'status' => SubscriptionBillingStatus::Failed,

            'attempted_at' => now(),

            'failure_reason' => 'Saldo rekening tidak mencukupi.',

            'metadata' => [
                'attempt_count' => 1,
            ],
        ])->save();

        return $billing->refresh();
    }
}
