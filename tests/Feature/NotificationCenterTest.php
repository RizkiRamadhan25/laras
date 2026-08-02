<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\SubscriptionBillingPosted;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_their_own_notifications(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $this->sendReminder(
            user: $user,
            subscriptionName: 'Netflix'
        );

        $this->sendReminder(
            user: $otherUser,
            subscriptionName: 'Spotify'
        );

        $this
            ->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Netflix')
            ->assertDontSee('Spotify');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = $this->completedUser();

        $this->sendReminder(
            user: $user,
            subscriptionName: 'Netflix'
        );

        $notification = $user
            ->unreadNotifications()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'notifications.read',
                    $notification->id
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNotNull(
            $notification
                ->fresh()
                ->read_at
        );
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->completedUser();

        $this->sendReminder(
            user: $user,
            subscriptionName: 'Netflix'
        );

        $this->sendReminder(
            user: $user,
            subscriptionName: 'Spotify'
        );

        $this->assertSame(
            2,
            $user->unreadNotifications()
                ->count()
        );

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'notifications.read-all'
                )
            )
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(
            0,
            $user->unreadNotifications()
                ->count()
        );
    }

    public function test_opening_posted_billing_redirects_to_transaction(): void
    {
        $user = $this->completedUser();

        $transaction = Transaction::factory()
            ->posted()
            ->expense()
            ->create([
                'user_id' => $user->id,
            ]);

        $user->notify(
            new SubscriptionBillingPosted(
                subscriptionId: 1,
                billingId: 1,
                transactionId: $transaction->id,
                subscriptionName: 'Netflix',
                amount: '186000.00',
                currencyCode: 'IDR',
                scheduledFor: '2026-08-10',
                accountName: 'BCA'
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
                'transactions.show',
                $transaction->id
            );

        $this->assertNotNull(
            $notification
                ->fresh()
                ->read_at
        );
    }

    public function test_notification_page_can_be_filtered_by_read_status(): void
    {
        $user = $this->completedUser();

        $this->sendReminder(
            user: $user,
            subscriptionName: 'Netflix'
        );

        $this->sendReminder(
            user: $user,
            subscriptionName: 'Spotify'
        );

        $readNotification = $user
            ->unreadNotifications()
            ->get()
            ->first(
                fn ($notification): bool => (
                    $notification->data[
                        'subscription_name'
                    ] ?? null
                ) === 'Netflix'
            );

        $this->assertNotNull(
            $readNotification
        );

        $readNotification->markAsRead();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'notifications.index',
                    ['filter' => 'unread']
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedFilter',
                'unread'
            )
            ->assertViewHas(
                'notifications',
                function ($notifications): bool {
                    $subscriptionNames =
                        $notifications
                            ->getCollection()
                            ->map(
                                fn ($notification) => $notification->data[
                                        'subscription_name'
                                    ] ?? null
                            )
                            ->filter()
                            ->values()
                            ->all();

                    return $subscriptionNames
                        === [
                            'Spotify',
                        ];
                }
            );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'notifications.index',
                    ['filter' => 'read']
                )
            )
            ->assertOk()
            ->assertViewHas(
                'selectedFilter',
                'read'
            )
            ->assertViewHas(
                'notifications',
                function ($notifications): bool {
                    $subscriptionNames =
                        $notifications
                            ->getCollection()
                            ->map(
                                fn ($notification) => $notification->data[
                                        'subscription_name'
                                    ] ?? null
                            )
                            ->filter()
                            ->values()
                            ->all();

                    return $subscriptionNames
                        === [
                            'Netflix',
                        ];
                }
            );
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = $this->completedUser();
        $otherUser = $this->completedUser();

        $this->sendReminder(
            user: $otherUser,
            subscriptionName: 'Spotify'
        );

        $notification = $otherUser
            ->unreadNotifications()
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'notifications.read',
                    $notification->id
                )
            )
            ->assertNotFound();

        $this->assertNull(
            $notification
                ->fresh()
                ->read_at
        );
    }

    private function sendReminder(
        User $user,
        string $subscriptionName
    ): void {
        $user->notify(
            new SubscriptionRenewalReminder(
                subscriptionId: 1,
                billingId: 1,
                subscriptionName: $subscriptionName,
                amount: '59000.00',
                currencyCode: 'IDR',
                scheduledFor: '2026-08-10',
                daysBefore: 3,
                accountName: 'BCA'
            )
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
}
