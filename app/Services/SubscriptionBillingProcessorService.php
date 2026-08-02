<?php

namespace App\Services;

use App\Enums\SubscriptionBillingStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionSource;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\User;
use App\Notifications\SubscriptionBillingFailed;
use App\Notifications\SubscriptionBillingPosted;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class SubscriptionBillingProcessorService
{
    public function __construct(
        private readonly TransactionPostingService $transactionPostingService,
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function retry(
        User $user,
        int $subscriptionId,
        int $billingId
    ): SubscriptionBilling {
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->findOrFail($subscriptionId);

        $billing = $subscription
            ->billings()
            ->findOrFail($billingId);

        if (
            $billing->status
            !== SubscriptionBillingStatus::Failed
        ) {
            throw new DomainException(
                'Hanya tagihan berstatus gagal yang dapat dicoba kembali.'
            );
        }

        if (
            $subscription->status
            !== SubscriptionStatus::Active
        ) {
            throw new DomainException(
                'Langganan harus aktif sebelum tagihan dapat dicoba kembali.'
            );
        }

        if (! $subscription->auto_post) {
            throw new DomainException(
                'Pencatatan otomatis tidak aktif untuk langganan ini.'
            );
        }

        $timezone = $user->preference()
            ->value('timezone')
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        $today = CarbonImmutable::today(
            $timezone
        );

        if (
            $billing->scheduled_for
                ->isAfter($today)
        ) {
            throw new DomainException(
                'Tagihan yang belum jatuh tempo tidak dapat dicoba kembali.'
            );
        }

        return $this->process(
            billing: $billing,
            reference: CarbonImmutable::now(
                $timezone
            ),
            force: true
        );
    }

    public function process(
        SubscriptionBilling $billing,
        ?DateTimeInterface $reference = null,
        bool $force = false
    ): SubscriptionBilling {
        $snapshot = SubscriptionBilling::query()
            ->findOrFail($billing->id);

        return DB::transaction(function () use (
            $snapshot,
            $reference,
            $force
        ): SubscriptionBilling {
            /*
             * Subscription dikunci lebih dahulu karena proses
             * cancel dan pause juga mengunci subscription.
             */
            $subscription =
                Subscription::query()
                    ->whereKey(
                        $snapshot->subscription_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

            $lockedBilling =
                SubscriptionBilling::query()
                    ->whereKey($snapshot->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if ($lockedBilling->status->isFinal()) {
                return $lockedBilling;
            }

            if (
                $subscription->status
                    !== SubscriptionStatus::Active
                || ! $subscription->auto_post
            ) {
                return $lockedBilling;
            }

            $subscription->loadMissing([
                'user.preference',
                'account',
                'financeCategory',
            ]);

            $user = $subscription->user;

            $timezone = $user
                ->preference?->timezone
                    ?? config(
                        'laras.defaults.timezone',
                        'Asia/Jakarta'
                    );

            $referenceAt = $reference !== null
                ? CarbonImmutable::instance(
                    $reference
                )->setTimezone($timezone)
                : CarbonImmutable::now($timezone);

            $scheduledOn = CarbonImmutable::parse(
                $lockedBilling
                    ->scheduled_for
                    ->toDateString(),
                $timezone
            )->startOfDay();

            /*
             * --force hanya mengabaikan jam tagihan,
             * bukan mengizinkan tanggal masa depan.
             */
            if (
                $scheduledOn->isAfter(
                    $referenceAt->startOfDay()
                )
            ) {
                return $lockedBilling;
            }

            $billingAt = CarbonImmutable::parse(
                $lockedBilling
                    ->scheduled_for
                    ->toDateString()
                .' '
                .$subscription->billing_time,
                $timezone
            );

            if (
                ! $force
                && $referenceAt->isBefore($billingAt)
            ) {
                return $lockedBilling;
            }

            $metadata =
                $lockedBilling->metadata ?? [];

            $metadata['attempt_count'] =
                (int) (
                    $metadata['attempt_count']
                    ?? 0
                ) + 1;

            $lockedBilling->forceFill([
                'status' => SubscriptionBillingStatus::Processing,

                'attempted_at' => now(),
                'failure_reason' => null,
                'metadata' => $metadata,
            ])->save();

            try {
                $transaction =
                    $this->transactionPostingService
                        ->postExpense(
                            user: $user,

                            accountId: $subscription->account_id,

                            categoryId: $subscription
                                ->finance_category_id,

                            amount: $lockedBilling->amount,

                            data: [
                                'source' => TransactionSource::System,

                                'occurred_at' => $billingAt,

                                'description' => mb_substr(
                                    'Langganan '
                                    .$subscription->name,
                                    0,
                                    160
                                ),

                                'counterparty' => mb_substr(
                                    $subscription->provider
                                        ?? $subscription->name,
                                    0,
                                    120
                                ),

                                'reference_number' => sprintf(
                                    'SUB-%d-%s',
                                    $subscription->id,
                                    $scheduledOn
                                        ->format(
                                            'Ymd'
                                        )
                                ),

                                'notes' => 'Dicatat otomatis oleh Laras dari jadwal langganan.',
                            ]
                        );
            } catch (DomainException $exception) {
                return $this->markFailed(
                    subscription: $subscription,
                    billing: $lockedBilling,
                    referenceAt: $referenceAt,
                    reason: $exception->getMessage()
                );
            }

            $lockedBilling->forceFill([
                'transaction_id' => $transaction->id,

                'status' => SubscriptionBillingStatus::Posted,

                'processed_at' => now(),
                'failure_reason' => null,
            ])->save();

            $this->subscriptionService
                ->advanceAfterSuccessfulBilling(
                    user: $user,

                    subscriptionId: $subscription->id,

                    billedFor: $lockedBilling
                        ->scheduled_for
                        ->toDateString()
                );

            $user->notify(
                new SubscriptionBillingPosted(
                    subscriptionId: $subscription->id,

                    billingId: $lockedBilling->id,

                    transactionId: $transaction->id,

                    subscriptionName: $subscription->name,

                    amount: $lockedBilling->amount,

                    currencyCode: $lockedBilling->currency_code,

                    scheduledFor: $lockedBilling
                        ->scheduled_for
                        ->toDateString(),

                    accountName: $subscription
                        ->account?->name
                            ?? 'Rekening'
                )
            );

            return $lockedBilling->refresh();
        }, 3);
    }

    private function markFailed(
        Subscription $subscription,
        SubscriptionBilling $billing,
        CarbonImmutable $referenceAt,
        string $reason
    ): SubscriptionBilling {
        $metadata = $billing->metadata ?? [];

        $notificationDate =
            $referenceAt->toDateString();

        $notifiedDates = array_map(
            'strval',
            $metadata[
                'failure_notifications_sent'
            ] ?? []
        );

        $sendNotification = ! in_array(
            $notificationDate,
            $notifiedDates,
            true
        );

        $metadata['last_failure_on'] =
            $notificationDate;

        if ($sendNotification) {
            $notifiedDates[] =
                $notificationDate;

            $metadata[
                'failure_notifications_sent'
            ] = collect($notifiedDates)
                ->unique()
                ->values()
                ->all();
        }

        $billing->forceFill([
            'status' => SubscriptionBillingStatus::Failed,

            'failure_reason' => $reason,
            'processed_at' => null,
            'metadata' => $metadata,
        ])->save();

        if ($sendNotification) {
            $subscription->user->notify(
                new SubscriptionBillingFailed(
                    subscriptionId: $subscription->id,

                    billingId: $billing->id,

                    subscriptionName: $subscription->name,

                    amount: $billing->amount,

                    currencyCode: $billing->currency_code,

                    scheduledFor: $billing
                        ->scheduled_for
                        ->toDateString(),

                    accountName: $subscription
                        ->account?->name
                            ?? 'Rekening',

                    failureReason: $reason
                )
            );
        }

        return $billing->refresh();
    }
}
