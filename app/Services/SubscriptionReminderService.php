<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Notifications\SubscriptionRenewalReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SubscriptionReminderService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function sendDueReminder(
        Subscription $subscription,
        CarbonImmutable $reference
    ): int {
        return DB::transaction(function () use (
            $subscription,
            $reference
        ): int {
            /*
             * Urutan lock selalu Subscription lalu Billing
             * agar konsisten dengan service pembatalan.
             */
            $lockedSubscription =
                Subscription::query()
                    ->whereKey($subscription->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (
                $lockedSubscription->status
                    !== SubscriptionStatus::Active
                || $lockedSubscription
                    ->next_billing_on === null
            ) {
                return 0;
            }

            $timezone = $lockedSubscription
                ->user()
                ->with('preference')
                ->firstOrFail()
                ->preference?->timezone
                    ?? config(
                        'laras.defaults.timezone',
                        'Asia/Jakarta'
                    );

            $today = $reference
                ->setTimezone($timezone)
                ->startOfDay();

            $nextBillingOn = CarbonImmutable::parse(
                $lockedSubscription
                    ->next_billing_on
                    ->toDateString(),
                $timezone
            )->startOfDay();

            $daysBefore = (int) $today->diffInDays(
                $nextBillingOn,
                false
            );

            $reminderDays = array_map(
                'intval',
                $lockedSubscription
                    ->reminder_days
                    ?? [
                        3,
                        1,
                    ]
            );

            if (
                $daysBefore < 0
                || ! in_array(
                    $daysBefore,
                    $reminderDays,
                    true
                )
            ) {
                return 0;
            }

            $billing =
                $this->subscriptionService
                    ->scheduleCurrentBilling(
                        $lockedSubscription
                    );

            if ($billing === null) {
                return 0;
            }

            $lockedBilling =
                SubscriptionBilling::query()
                    ->whereKey($billing->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            $metadata =
                $lockedBilling->metadata ?? [];

            $sentDays = array_map(
                'intval',
                $metadata['reminders_sent']
                    ?? []
            );

            if (
                in_array(
                    $daysBefore,
                    $sentDays,
                    true
                )
            ) {
                return 0;
            }

            $lockedSubscription->loadMissing([
                'user',
                'account',
            ]);

            $lockedSubscription->user->notify(
                new SubscriptionRenewalReminder(
                    subscriptionId: $lockedSubscription->id,

                    billingId: $lockedBilling->id,

                    subscriptionName: $lockedSubscription->name,

                    amount: $lockedBilling->amount,

                    currencyCode: $lockedBilling->currency_code,

                    scheduledFor: $lockedBilling
                        ->scheduled_for
                        ->toDateString(),

                    daysBefore: $daysBefore,

                    accountName: $lockedSubscription
                        ->account?->name
                            ?? 'Rekening'
                )
            );

            $sentDays[] = $daysBefore;

            $metadata['reminders_sent'] =
                collect($sentDays)
                    ->unique()
                    ->sortDesc()
                    ->values()
                    ->all();

            $lockedBilling->forceFill([
                'metadata' => $metadata,
            ])->save();

            return 1;
        }, 3);
    }
}
