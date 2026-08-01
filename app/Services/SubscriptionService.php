<?php

namespace App\Services;

use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Enums\SubscriptionIntervalUnit;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use ValueError;

class SubscriptionService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(
        User $user,
        array $data
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $data
        ): Subscription {
            $account = $this->ownedActiveAccount(
                $user,
                (int) ($data['account_id'] ?? 0)
            );

            $category = $this->ownedExpenseCategory(
                $user,
                (int) (
                    $data['finance_category_id']
                    ?? 0
                )
            );

            $payload = $this->normalizePayload(
                user: $user,
                account: $account,
                category: $category,
                data: $data
            );

            $subscription = $user
                ->subscriptions()
                ->create([
                    ...$payload,
                    'status' =>
                        SubscriptionStatus::Active,

                    'last_billed_on' => null,
                    'paused_at' => null,
                    'cancelled_at' => null,
                ]);

            $this->scheduleCurrentBilling(
                $subscription
            );

            return $subscription->load([
                'account',
                'financeCategory',
                'billings',
            ]);
        }, 3);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        User $user,
        int $subscriptionId,
        array $data
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $subscriptionId,
            $data
        ): Subscription {
            $subscription =
                $this->lockOwnedSubscription(
                    $user,
                    $subscriptionId
                );

            if (
                in_array(
                    $subscription->status,
                    [
                        SubscriptionStatus::Cancelled,
                        SubscriptionStatus::Expired,
                    ],
                    true
                )
            ) {
                throw new DomainException(
                    'Langganan yang dihentikan atau berakhir tidak dapat diedit.'
                );
            }

            $account = $this->ownedActiveAccount(
                $user,
                (int) ($data['account_id'] ?? 0)
            );

            $category = $this->ownedExpenseCategory(
                $user,
                (int) (
                    $data['finance_category_id']
                    ?? 0
                )
            );

            $oldNextBillingOn =
                $subscription->next_billing_on
                    ?->toDateString();

            $payload = $this->normalizePayload(
                user: $user,
                account: $account,
                category: $category,
                data: $data
            );

            $subscription->update($payload);

            $newNextBillingOn =
                $subscription->fresh()
                    ->next_billing_on
                    ?->toDateString();

            if (
                $oldNextBillingOn
                !== $newNextBillingOn
            ) {
                $subscription->billings()
                    ->where(
                        'status',
                        SubscriptionBillingStatus
                            ::Scheduled->value
                    )
                    ->whereNull('transaction_id')
                    ->update([
                        'status' =>
                            SubscriptionBillingStatus
                                ::Cancelled,

                        'metadata' => json_encode([
                            'reason' =>
                                'Jadwal langganan diperbarui.',
                        ]),
                    ]);
            }

            $this->scheduleCurrentBilling(
                $subscription->refresh()
            );

            return $subscription->refresh()->load([
                'account',
                'financeCategory',
                'billings',
            ]);
        }, 3);
    }

    public function pause(
        User $user,
        int $subscriptionId
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $subscriptionId
        ): Subscription {
            $subscription =
                $this->lockOwnedSubscription(
                    $user,
                    $subscriptionId
                );

            if (
                $subscription->status
                === SubscriptionStatus::Cancelled
            ) {
                throw new DomainException(
                    'Langganan yang dihentikan tidak dapat dijeda.'
                );
            }

            if (
                $subscription->status
                === SubscriptionStatus::Expired
            ) {
                throw new DomainException(
                    'Langganan yang telah berakhir tidak dapat dijeda.'
                );
            }

            $subscription->forceFill([
                'status' =>
                    SubscriptionStatus::Paused,

                'paused_at' => now(),
            ])->save();

            return $subscription->refresh();
        }, 3);
    }

    public function resume(
        User $user,
        int $subscriptionId
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $subscriptionId
        ): Subscription {
            $subscription =
                $this->lockOwnedSubscription(
                    $user,
                    $subscriptionId
                );

            if (
                $subscription->status
                !== SubscriptionStatus::Paused
            ) {
                throw new DomainException(
                    'Hanya langganan yang dijeda yang dapat diaktifkan kembali.'
                );
            }

            if (
                $subscription->end_on !== null
                && $subscription->end_on
                    ->isBefore(
                        CarbonImmutable::today(
                            $this->userTimezone($user)
                        )
                    )
            ) {
                $subscription->forceFill([
                    'status' =>
                        SubscriptionStatus::Expired,

                    'next_billing_on' => null,
                    'paused_at' => null,
                ])->save();

                return $subscription->refresh();
            }

            $subscription->forceFill([
                'status' =>
                    SubscriptionStatus::Active,

                'paused_at' => null,
                'cancelled_at' => null,
            ])->save();

            $this->scheduleCurrentBilling(
                $subscription->refresh()
            );

            return $subscription->refresh();
        }, 3);
    }

    public function cancel(
        User $user,
        int $subscriptionId
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $subscriptionId
        ): Subscription {
            $subscription =
                $this->lockOwnedSubscription(
                    $user,
                    $subscriptionId
                );

            if (
                $subscription->status
                === SubscriptionStatus::Cancelled
            ) {
                return $subscription;
            }

            $subscription->forceFill([
                'status' =>
                    SubscriptionStatus::Cancelled,

                'next_billing_on' => null,
                'paused_at' => null,
                'cancelled_at' => now(),
            ])->save();

            $subscription->billings()
                ->where(
                    'status',
                    SubscriptionBillingStatus
                        ::Scheduled->value
                )
                ->whereNull('transaction_id')
                ->update([
                    'status' =>
                        SubscriptionBillingStatus
                            ::Cancelled,

                    'metadata' => json_encode([
                        'reason' =>
                            'Langganan dihentikan.',
                    ]),
                ]);

            return $subscription->refresh();
        }, 3);
    }

    public function archive(
        User $user,
        int $subscriptionId
    ): void {
        DB::transaction(function () use (
            $user,
            $subscriptionId
        ): void {
            $subscription =
                $this->lockOwnedSubscription(
                    $user,
                    $subscriptionId
                );

            if (
                ! in_array(
                    $subscription->status,
                    [
                        SubscriptionStatus::Cancelled,
                        SubscriptionStatus::Expired,
                    ],
                    true
                )
            ) {
                throw new DomainException(
                    'Langganan harus dihentikan atau berakhir sebelum diarsipkan.'
                );
            }

            $subscription->delete();
        }, 3);
    }

    public function scheduleCurrentBilling(
        Subscription $subscription
    ): ?SubscriptionBilling {
        if (
            ! $subscription->status
                ->canGenerateBilling()
            || $subscription->next_billing_on === null
        ) {
            return null;
        }

        $scheduledFor = $subscription
            ->next_billing_on
            ->toDateString();

        $billing = SubscriptionBilling::query()
            ->where(
                'subscription_id',
                $subscription->id
            )
            ->whereDate(
                'scheduled_for',
                $scheduledFor
            )
            ->first();

        if ($billing === null) {
            try {
                return SubscriptionBilling::query()
                    ->create([
                        'subscription_id' =>
                            $subscription->id,

                        'user_id' =>
                            $subscription->user_id,

                        'transaction_id' => null,

                        'scheduled_for' =>
                            $scheduledFor,

                        'amount' =>
                            $subscription->amount,

                        'currency_code' =>
                            $subscription->currency_code,

                        'status' =>
                            SubscriptionBillingStatus
                                ::Scheduled,

                        'attempted_at' => null,
                        'processed_at' => null,
                        'failure_reason' => null,
                        'metadata' => null,
                    ])
                    ->refresh();
            } catch (
                UniqueConstraintViolationException
            ) {
                /*
                * Proses scheduler lain mungkin telah membuat
                * billing yang sama lebih dahulu.
                */
                $billing =
                    SubscriptionBilling::query()
                        ->where(
                            'subscription_id',
                            $subscription->id
                        )
                        ->whereDate(
                            'scheduled_for',
                            $scheduledFor
                        )
                        ->firstOrFail();
            }
        }

        if (
            $billing->status
            === SubscriptionBillingStatus::Posted
        ) {
            return $billing;
        }

        if ($billing->status->isFinal()) {
            return $billing;
        }

        $billing->fill([
            'user_id' => $subscription->user_id,
            'amount' => $subscription->amount,

            'currency_code' =>
                $subscription->currency_code,

            /*
            * Billing failed boleh dicoba kembali pada
            * pemeriksaan scheduler berikutnya.
            */
            'status' =>
                SubscriptionBillingStatus::Scheduled,

            'attempted_at' => null,
            'processed_at' => null,
            'failure_reason' => null,
        ]);

        $billing->save();

        return $billing->refresh();
    }

    public function advanceAfterSuccessfulBilling(
        User $user,
        int $subscriptionId,
        DateTimeInterface|string $billedFor
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $subscriptionId,
            $billedFor
        ): Subscription {
            $subscription =
                $this->lockOwnedSubscription(
                    $user,
                    $subscriptionId
                );

            $billingDate = $this->normalizeDate(
                $user,
                $billedFor,
                'Tanggal tagihan'
            );

            if (
                $subscription->next_billing_on
                    === null
                || ! $subscription
                    ->next_billing_on
                    ->isSameDay($billingDate)
            ) {
                throw new DomainException(
                    'Tanggal tagihan tidak sesuai dengan siklus aktif langganan.'
                );
            }

            $nextBillingOn =
                $this->calculateNextBillingOn(
                    subscription: $subscription,
                    currentBillingOn: $billingDate
                );

            $expired = $subscription->end_on
                !== null
                && $nextBillingOn->isAfter(
                    $subscription->end_on
                );

            $subscription->forceFill([
                'last_billed_on' => $billingDate,

                'next_billing_on' => $expired
                    ? null
                    : $nextBillingOn,

                'status' => $expired
                    ? SubscriptionStatus::Expired
                    : SubscriptionStatus::Active,
            ])->save();

            if (! $expired) {
                $this->scheduleCurrentBilling(
                    $subscription->refresh()
                );
            }

            return $subscription->refresh();
        }, 3);
    }

    public function calculateNextBillingOn(
        Subscription $subscription,
        DateTimeInterface|string|null $currentBillingOn = null
    ): CarbonImmutable {
        $timezone = $this->userTimezone(
            $subscription->user
        );

        $current = $currentBillingOn === null
            ? CarbonImmutable::instance(
                $subscription->next_billing_on
                    ?? $subscription->started_on
            )
            : CarbonImmutable::parse(
                $currentBillingOn,
                $timezone
            );

        $count = $subscription->interval_count;

        return match (
            $subscription->interval_unit
        ) {
            SubscriptionIntervalUnit::Day =>
                $current->addDays($count),

            SubscriptionIntervalUnit::Week =>
                $current->addWeeks($count),

            SubscriptionIntervalUnit::Month =>
                $this->nextMonthlyDate(
                    subscription: $subscription,
                    current: $current,
                    count: $count
                ),

            SubscriptionIntervalUnit::Year =>
                $this->nextYearlyDate(
                    subscription: $subscription,
                    current: $current,
                    count: $count
                ),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePayload(
        User $user,
        Account $account,
        FinanceCategory $category,
        array $data
    ): array {
        $name = trim(
            (string) ($data['name'] ?? '')
        );

        if ($name === '') {
            throw new DomainException(
                'Nama langganan wajib diisi.'
            );
        }

        if (mb_strlen($name) > 160) {
            throw new DomainException(
                'Nama langganan maksimal 160 karakter.'
            );
        }

        $amount = $this->normalizePositiveMoney(
            $data['amount'] ?? null
        );

        $intervalUnit =
            $this->subscriptionIntervalUnit(
                $data['interval_unit']
                    ?? SubscriptionIntervalUnit::Month
            );

        $intervalCount = filter_var(
            $data['interval_count'] ?? 1,
            FILTER_VALIDATE_INT
        );

        if (
            $intervalCount === false
            || $intervalCount < 1
            || $intervalCount > 365
        ) {
            throw new DomainException(
                'Interval langganan harus antara 1 dan 365.'
            );
        }

        $startedOn = $this->normalizeDate(
            $user,
            $data['started_on'] ?? null,
            'Tanggal mulai'
        );

        $nextBillingOn = isset(
            $data['next_billing_on']
        ) && trim(
            (string) $data['next_billing_on']
        ) !== ''
            ? $this->normalizeDate(
                $user,
                $data['next_billing_on'],
                'Tanggal tagihan berikutnya'
            )
            : $startedOn;

        $endOn = isset($data['end_on'])
            && trim(
                (string) $data['end_on']
            ) !== ''
                ? $this->normalizeDate(
                    $user,
                    $data['end_on'],
                    'Tanggal berakhir'
                )
                : null;

        if ($nextBillingOn->isBefore($startedOn)) {
            throw new DomainException(
                'Tanggal tagihan berikutnya tidak boleh mendahului tanggal mulai.'
            );
        }

        if (
            $endOn !== null
            && $endOn->isBefore($startedOn)
        ) {
            throw new DomainException(
                'Tanggal berakhir tidak boleh mendahului tanggal mulai.'
            );
        }

        if (
            $endOn !== null
            && $nextBillingOn->isAfter($endOn)
        ) {
            throw new DomainException(
                'Tanggal tagihan berikutnya tidak boleh melewati tanggal berakhir.'
            );
        }

        $billingTime = $this->normalizeTime(
            $data['billing_time']
                ?? '08:00'
        );

        return [
            'account_id' => $account->id,

            'finance_category_id' =>
                $category->id,

            'name' => $name,

            'provider' => $this->nullableString(
                $data['provider'] ?? null
            ),

            'amount' => $amount,

            'currency_code' =>
                $account->currency_code,

            'interval_unit' => $intervalUnit,
            'interval_count' => $intervalCount,

            'started_on' => $startedOn,
            'next_billing_on' => $nextBillingOn,
            'end_on' => $endOn,
            'billing_time' => $billingTime,

            'auto_post' => (bool) (
                $data['auto_post'] ?? true
            ),

            'reminder_days' =>
                $this->normalizeReminderDays(
                    $data['reminder_days']
                        ?? [
                            3,
                            1,
                        ]
                ),

            'metadata' => is_array(
                $data['metadata'] ?? null
            )
                ? $data['metadata']
                : null,
        ];
    }

    private function ownedActiveAccount(
        User $user,
        int $accountId
    ): Account {
        $account = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->find($accountId);

        if ($account === null) {
            throw (new ModelNotFoundException())
                ->setModel(
                    Account::class,
                    [$accountId]
                );
        }

        return $account;
    }

    private function ownedExpenseCategory(
        User $user,
        int $categoryId
    ): FinanceCategory {
        $category = FinanceCategory::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->find($categoryId);

        if ($category === null) {
            throw (new ModelNotFoundException())
                ->setModel(
                    FinanceCategory::class,
                    [$categoryId]
                );
        }

        if (
            ! in_array(
                $category->flow_type,
                [
                    FinanceFlowType::Expense,
                    FinanceFlowType::Both,
                ],
                true
            )
        ) {
            throw new DomainException(
                'Langganan harus menggunakan kategori pengeluaran.'
            );
        }

        return $category;
    }

    private function lockOwnedSubscription(
        User $user,
        int $subscriptionId
    ): Subscription {
        return Subscription::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->findOrFail($subscriptionId);
    }

    private function subscriptionIntervalUnit(
        mixed $value
    ): SubscriptionIntervalUnit {
        if (
            $value
            instanceof SubscriptionIntervalUnit
        ) {
            return $value;
        }

        try {
            return SubscriptionIntervalUnit::from(
                (string) $value
            );
        } catch (ValueError) {
            throw new DomainException(
                'Satuan interval langganan tidak valid.'
            );
        }
    }

    private function normalizeDate(
        User $user,
        mixed $value,
        string $label
    ): CarbonImmutable {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                $value
            )->startOfDay();
        }

        $normalized = trim(
            (string) $value
        );

        if ($normalized === '') {
            throw new DomainException(
                $label.' wajib diisi.'
            );
        }

        try {
            return CarbonImmutable::parse(
                $normalized,
                $this->userTimezone($user)
            )->startOfDay();
        } catch (\Throwable) {
            throw new DomainException(
                $label.' tidak valid.'
            );
        }
    }

    private function nextMonthlyDate(
        Subscription $subscription,
        CarbonImmutable $current,
        int $count
    ): CarbonImmutable {
        $anchorDay =
            $subscription->started_on->day;

        $targetMonth = $current
            ->startOfMonth()
            ->addMonths($count);

        return $targetMonth->day(
            min(
                $anchorDay,
                $targetMonth->daysInMonth
            )
        );
    }

    private function nextYearlyDate(
        Subscription $subscription,
        CarbonImmutable $current,
        int $count
    ): CarbonImmutable {
        $targetYear = $current->year + $count;

        $anchorMonth =
            $subscription->started_on->month;

        $anchorDay =
            $subscription->started_on->day;

        $target = CarbonImmutable::create(
            year: $targetYear,
            month: $anchorMonth,
            day: 1,
            timezone: $current->timezone
        );

        return $target->day(
            min(
                $anchorDay,
                $target->daysInMonth
            )
        );
    }

    /**
     * @return list<int>
     */
    private function normalizeReminderDays(
        mixed $value
    ): array {
        $values = is_array($value)
            ? $value
            : [$value];

        $normalized = collect($values)
            ->map(function (mixed $day): int {
                $integer = filter_var(
                    $day,
                    FILTER_VALIDATE_INT
                );

                if (
                    $integer === false
                    || $integer < 0
                    || $integer > 30
                ) {
                    throw new DomainException(
                        'Hari pengingat harus antara 0 dan 30.'
                    );
                }

                return $integer;
            })
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($normalized === []) {
            return [
                3,
                1,
            ];
        }

        return $normalized;
    }

    private function normalizeTime(
        mixed $value
    ): string {
        $normalized = trim(
            (string) $value
        );

        if (
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $normalized
            ) !== 1
        ) {
            throw new DomainException(
                'Waktu penagihan tidak valid.'
            );
        }

        return $normalized.':00';
    }

    private function normalizePositiveMoney(
        mixed $value
    ): string {
        $normalized = trim(
            (string) $value
        );

        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $normalized
            ) !== 1
        ) {
            throw new DomainException(
                'Nominal langganan tidak valid.'
            );
        }

        $amount = bcadd(
            $normalized,
            '0',
            2
        );

        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new DomainException(
                'Nominal langganan harus lebih besar dari nol.'
            );
        }

        if (
            bccomp(
                $amount,
                '9999999999999999.99',
                2
            ) > 0
        ) {
            throw new DomainException(
                'Nominal langganan melebihi batas.'
            );
        }

        return $amount;
    }

    private function userTimezone(
        User $user
    ): string {
        return $user->preference()
            ->value('timezone')
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $normalized = trim(
            (string) $value
        );

        return $normalized === ''
            ? null
            : $normalized;
    }
}
