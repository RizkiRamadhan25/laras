<?php

namespace App\Services;

use App\Enums\SubscriptionBillingStatus;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Throwable;

class SubscriptionAutomationService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionReminderService $reminderService,
        private readonly SubscriptionBillingProcessorService $billingProcessor
    ) {}

    /**
     * @return array{
     *     subscriptions_checked: int,
     *     reminders_sent: int,
     *     billings_posted: int,
     *     billings_failed: int,
     *     manual_due: int,
     *     errors: int
     * }
     */
    public function run(
        ?string $date = null,
        bool $force = false
    ): array {
        $summary = [
            'subscriptions_checked' => 0,
            'reminders_sent' => 0,
            'billings_posted' => 0,
            'billings_failed' => 0,
            'manual_due' => 0,
            'errors' => 0,
        ];

        Subscription::query()
            ->active()
            ->whereNotNull('next_billing_on')
            ->with([
                'user.preference',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function (
                    $subscriptions
                ) use (
                    &$summary,
                    $date,
                    $force
                ): void {
                    foreach (
                        $subscriptions as $subscription
                    ) {
                        $summary[
                            'subscriptions_checked'
                        ]++;

                        try {
                            $reference =
                                $this->referenceFor(
                                    user: $subscription->user,

                                    date: $date,
                                    force: $force
                                );

                            $billing =
                                $this
                                    ->subscriptionService
                                    ->scheduleCurrentBilling(
                                        $subscription
                                    );

                            $summary[
                                'reminders_sent'
                            ] += $this
                                ->reminderService
                                ->sendDueReminder(
                                    subscription: $subscription,

                                    reference: $reference
                                );

                            if ($billing === null) {
                                continue;
                            }

                            $billingDate =
                                $billing
                                    ->scheduled_for
                                    ->toDateString();

                            $today = $reference
                                ->toDateString();

                            if ($billingDate > $today) {
                                continue;
                            }

                            if (
                                ! $subscription->auto_post
                            ) {
                                $summary['manual_due']++;

                                continue;
                            }

                            $previousStatus =
                                $billing->status;

                            $processed =
                                $this
                                    ->billingProcessor
                                    ->process(
                                        billing: $billing,

                                        reference: $reference,

                                        force: $force
                                    );

                            if (
                                $processed->status
                                    === SubscriptionBillingStatus::Posted
                                && $previousStatus
                                    !== SubscriptionBillingStatus::Posted
                            ) {
                                $summary[
                                    'billings_posted'
                                ]++;
                            } elseif (
                                $processed->status
                                    === SubscriptionBillingStatus::Failed
                            ) {
                                $summary[
                                    'billings_failed'
                                ]++;
                            }
                        } catch (Throwable $exception) {
                            report($exception);

                            $summary['errors']++;
                        }
                    }
                }
            );

        return $summary;
    }

    private function referenceFor(
        User $user,
        ?string $date,
        bool $force
    ): CarbonImmutable {
        $timezone = $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        if ($date === null) {
            return CarbonImmutable::now(
                $timezone
            );
        }

        $time = $force
            ? '23:59:59'
            : CarbonImmutable::now(
                $timezone
            )->format('H:i:s');

        return CarbonImmutable::parse(
            $date.' '.$time,
            $timezone
        );
    }
}
