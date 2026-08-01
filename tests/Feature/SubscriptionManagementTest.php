<?php

namespace Tests\Feature;

use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_subscription_pages(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,

            'finance_category_id' =>
                $category->id,

            'name' => 'Netflix',
        ]);

        $this
            ->actingAs($user)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('Netflix');

        $this
            ->actingAs($user)
            ->get(route('subscriptions.create'))
            ->assertOk()
            ->assertSee('Tambah langganan baru');
    }

    public function test_user_can_create_subscription(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $response = $this
            ->actingAs($user)
            ->post(
                route('subscriptions.store'),
                $this->payload(
                    account: $account,
                    category: $category,
                    data: [
                        'name' => 'Netflix',
                    ]
                )
            );

        $subscription = Subscription::query()
            ->firstOrFail();

        $response
            ->assertRedirectToRoute(
                'subscriptions.show',
                $subscription->id
            )
            ->assertSessionHas('status');

        $this->assertDatabaseHas(
            'subscriptions',
            [
                'user_id' => $user->id,
                'name' => 'Netflix',
                'status' => 'active',
            ]
        );
    }

    public function test_user_can_update_own_subscription(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'name' => 'Nama lama',
            ]);

        $this
            ->actingAs($user)
            ->put(
                route(
                    'subscriptions.update',
                    $subscription->id
                ),
                $this->payload(
                    account: $account,
                    category: $category,
                    data: [
                        'name' => 'Nama baru',
                    ]
                )
            )
            ->assertRedirectToRoute(
                'subscriptions.show',
                $subscription->id
            );

        $this->assertSame(
            'Nama baru',
            $subscription->fresh()->name
        );
    }

    public function test_user_cannot_view_another_users_subscription(): void
    {
        [$user] = $this->context();

        [
            $otherUser,
            $otherAccount,
            $otherCategory,
        ] = $this->context();

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $otherUser->id,
                'account_id' =>
                    $otherAccount->id,

                'finance_category_id' =>
                    $otherCategory->id,
            ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'subscriptions.show',
                    $subscription->id
                )
            )
            ->assertNotFound();
    }

    public function test_subscription_can_be_paused_and_resumed(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'status' =>
                    SubscriptionStatus::Active,
            ]);

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'subscriptions.pause',
                    $subscription->id
                )
            )
            ->assertRedirect();

        $this->assertSame(
            SubscriptionStatus::Paused,
            $subscription->fresh()->status
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'subscriptions.resume',
                    $subscription->id
                )
            )
            ->assertRedirect();

        $this->assertSame(
            SubscriptionStatus::Active,
            $subscription->fresh()->status
        );
    }

    public function test_subscription_can_be_cancelled(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,
            ]);

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'subscriptions.cancel',
                    $subscription->id
                )
            )
            ->assertRedirect();

        $this->assertSame(
            SubscriptionStatus::Cancelled,
            $subscription->fresh()->status
        );
    }

    public function test_notification_opens_owned_subscription(): void
    {
        [
            $user,
            $account,
            $category,
        ] = $this->context();

        $subscription = Subscription::factory()
            ->create([
                'user_id' => $user->id,
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,
            ]);

        $user->notify(
            new SubscriptionRenewalReminder(
                subscriptionId:
                    $subscription->id,

                billingId: 1,
                subscriptionName:
                    $subscription->name,

                amount: $subscription->amount,
                currencyCode: 'IDR',
                scheduledFor: '2026-08-10',
                daysBefore: 3,
                accountName: $account->name
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
                'subscriptions.show',
                $subscription->id
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
            'initial_balance' => '500000.00',
            'cached_balance' => '500000.00',
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()
            ->expense()
            ->create([
                'user_id' => $user->id,

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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function payload(
        Account $account,
        FinanceCategory $category,
        array $data = []
    ): array {
        return array_merge(
            [
                'account_id' => $account->id,

                'finance_category_id' =>
                    $category->id,

                'name' => 'Spotify',
                'provider' => 'Spotify',
                'amount' => '59000',

                'interval_unit' => 'month',
                'interval_count' => 1,

                'started_on' => '2026-08-01',

                'next_billing_on' =>
                    '2026-08-10',

                'end_on' => null,
                'billing_time' => '08:00',
                'auto_post' => '1',

                'reminder_days' => [
                    '3',
                    '1',
                ],
            ],
            $data
        );
    }
}
