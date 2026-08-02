<?php

namespace App\Services;

use App\Enums\FinanceFlowType;
use App\Enums\TransactionEntryRole;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use ValueError;

class TransactionPostingService
{
    public function __construct(
        private readonly BudgetUsageSyncService $budgetUsageSyncService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function postIncome(
        User $user,
        int $accountId,
        int $categoryId,
        mixed $amount,
        array $data = []
    ): Transaction {
        $normalizedAmount = $this->normalizePositiveMoney(
            $amount,
            'Nominal pemasukan'
        );

        return DB::transaction(function () use (
            $user,
            $accountId,
            $categoryId,
            $normalizedAmount,
            $data
        ): Transaction {
            $account = $this
                ->lockOwnedActiveAccounts($user, [$accountId])
                ->get($accountId);

            $category = $this->ownedCategory(
                $user,
                $categoryId,
                FinanceFlowType::Income
            );

            $transaction = $this->createPostedTransaction(
                user: $user,
                type: TransactionType::Income,
                data: $data,
                defaultDescription: 'Pemasukan'
            );

            $transaction->entries()->create([
                'account_id' => $account->id,
                'finance_category_id' => $category->id,
                'amount' => $normalizedAmount,
                'role' => TransactionEntryRole::Principal,
                'memo' => $data['memo'] ?? null,
            ]);

            $this->applyBalanceDelta(
                $account,
                $normalizedAmount
            );

            return $transaction->load([
                'entries.account',
                'entries.financeCategory',
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function postExpense(
        User $user,
        int $accountId,
        int $categoryId,
        mixed $amount,
        array $data = []
    ): Transaction {
        $normalizedAmount = $this->normalizePositiveMoney(
            $amount,
            'Nominal pengeluaran'
        );

        $negativeAmount = bcsub(
            '0',
            $normalizedAmount,
            2
        );

        return DB::transaction(function () use (
            $user,
            $accountId,
            $categoryId,
            $negativeAmount,
            $data
        ): Transaction {
            $account = $this
                ->lockOwnedActiveAccounts($user, [$accountId])
                ->get($accountId);

            $category = $this->ownedCategory(
                $user,
                $categoryId,
                FinanceFlowType::Expense
            );

            $this->ensureBalanceIsSufficient(
                $account,
                $negativeAmount
            );

            $transaction = $this->createPostedTransaction(
                user: $user,
                type: TransactionType::Expense,
                data: $data,
                defaultDescription: 'Pengeluaran'
            );

            $transaction->entries()->create([
                'account_id' => $account->id,
                'finance_category_id' => $category->id,
                'amount' => $negativeAmount,
                'role' => TransactionEntryRole::Principal,
                'memo' => $data['memo'] ?? null,
            ]);

            $this->applyBalanceDelta(
                $account,
                $negativeAmount
            );

            $this->budgetUsageSyncService
                ->syncForTransaction(
                    $transaction
                );

            return $transaction->load([
                'entries.account',
                'entries.financeCategory',
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function postTransfer(
        User $user,
        int $sourceAccountId,
        int $destinationAccountId,
        mixed $amount,
        mixed $adminFee = '0',
        array $data = []
    ): Transaction {
        if ($sourceAccountId === $destinationAccountId) {
            throw new DomainException(
                'Rekening sumber dan tujuan harus berbeda.'
            );
        }

        $normalizedAmount = $this->normalizePositiveMoney(
            $amount,
            'Nominal transfer'
        );

        $normalizedFee = $this->normalizeNonNegativeMoney(
            $adminFee,
            'Biaya admin'
        );

        return DB::transaction(function () use (
            $user,
            $sourceAccountId,
            $destinationAccountId,
            $normalizedAmount,
            $normalizedFee,
            $data
        ): Transaction {
            $accounts = $this->lockOwnedActiveAccounts(
                $user,
                [
                    $sourceAccountId,
                    $destinationAccountId,
                ]
            );

            $sourceAccount = $accounts->get($sourceAccountId);
            $destinationAccount = $accounts->get(
                $destinationAccountId
            );

            if (
                $sourceAccount->currency_code
                !== $destinationAccount->currency_code
            ) {
                throw new DomainException(
                    'Transfer antar mata uang belum didukung.'
                );
            }

            $totalOutgoing = bcadd(
                $normalizedAmount,
                $normalizedFee,
                2
            );

            $sourceDelta = bcsub(
                '0',
                $totalOutgoing,
                2
            );

            $this->ensureBalanceIsSufficient(
                $sourceAccount,
                $sourceDelta
            );

            $transaction = $this->createPostedTransaction(
                user: $user,
                type: TransactionType::Transfer,
                data: $data,
                defaultDescription: sprintf(
                    'Transfer dari %s ke %s',
                    $sourceAccount->name,
                    $destinationAccount->name
                )
            );

            $entries = [
                [
                    'account_id' => $sourceAccount->id,
                    'finance_category_id' => null,
                    'amount' => bcsub(
                        '0',
                        $normalizedAmount,
                        2
                    ),
                    'role' => TransactionEntryRole::Principal,
                    'memo' => 'Transfer keluar',
                ],
                [
                    'account_id' => $destinationAccount->id,
                    'finance_category_id' => null,
                    'amount' => $normalizedAmount,
                    'role' => TransactionEntryRole::Principal,
                    'memo' => 'Transfer masuk',
                ],
            ];

            if (bccomp($normalizedFee, '0.00', 2) > 0) {
                $feeCategory = $this->defaultAdminFeeCategory(
                    $user
                );

                $entries[] = [
                    'account_id' => $sourceAccount->id,
                    'finance_category_id' => $feeCategory->id,
                    'amount' => bcsub(
                        '0',
                        $normalizedFee,
                        2
                    ),
                    'role' => TransactionEntryRole::Fee,
                    'memo' => 'Biaya admin transfer',
                ];
            }

            $transaction->entries()->createMany($entries);

            $this->applyBalanceDelta(
                $sourceAccount,
                $sourceDelta
            );

            $this->applyBalanceDelta(
                $destinationAccount,
                $normalizedAmount
            );

            $this->budgetUsageSyncService
                ->syncForTransaction(
                    $transaction
                );

            return $transaction->load([
                'entries.account',
                'entries.financeCategory',
            ]);
        }, 3);
    }

    public function cancel(
        User $user,
        int $transactionId,
        ?string $reason = null
    ): Transaction {
        return DB::transaction(function () use (
            $user,
            $transactionId,
            $reason
        ): Transaction {
            $transaction = Transaction::query()
                ->where('user_id', $user->id)
                ->with('entries')
                ->lockForUpdate()
                ->findOrFail($transactionId);

            if (
                $transaction->status
                !== TransactionStatus::Posted
            ) {
                throw new DomainException(
                    'Hanya transaksi tercatat yang dapat dibatalkan.'
                );
            }

            $accountIds = $transaction->entries
                ->pluck('account_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $accounts = Account::withTrashed()
                ->where('user_id', $user->id)
                ->whereIn('id', $accountIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($accounts->count() !== count($accountIds)) {
                throw (new ModelNotFoundException)
                    ->setModel(Account::class, $accountIds);
            }

            $entryTotals = [];

            foreach ($transaction->entries as $entry) {
                $currentTotal = $entryTotals[$entry->account_id]
                    ?? '0.00';

                $entryTotals[$entry->account_id] = bcadd(
                    $currentTotal,
                    $entry->amount,
                    2
                );
            }

            $metadata = $transaction->metadata ?? [];

            if ($reason !== null && trim($reason) !== '') {
                $metadata['cancellation_reason'] = trim($reason);
            }

            $transaction->forceFill([
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
                'metadata' => $metadata === [] ? null : $metadata,
            ])->save();

            foreach ($entryTotals as $accountId => $entryTotal) {
                $account = $accounts->get($accountId);

                /*
                 * Efek pembatalan adalah kebalikan dari seluruh entry
                 * pada rekening tersebut.
                 */
                $reverseDelta = bcsub(
                    '0',
                    $entryTotal,
                    2
                );

                $this->applyBalanceDelta(
                    $account,
                    $reverseDelta,
                    allowNegative: true
                );
            }

            $this->budgetUsageSyncService
                ->syncForTransaction(
                    $transaction
                );

            return $transaction->refresh()->load([
                'entries.account',
                'entries.financeCategory',
            ]);
        }, 3);
    }

    /**
     * @param  list<int>  $accountIds
     * @return Collection<int, Account>
     */
    private function lockOwnedActiveAccounts(
        User $user,
        array $accountIds
    ): Collection {
        $uniqueAccountIds = collect($accountIds)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('id', $uniqueAccountIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($accounts->count() !== count($uniqueAccountIds)) {
            throw (new ModelNotFoundException)
                ->setModel(Account::class, $uniqueAccountIds);
        }

        return $accounts;
    }

    private function ownedCategory(
        User $user,
        int $categoryId,
        FinanceFlowType $requiredFlow
    ): FinanceCategory {
        $category = FinanceCategory::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->findOrFail($categoryId);

        $allowedFlows = [
            $requiredFlow,
            FinanceFlowType::Both,
        ];

        if (! in_array(
            $category->flow_type,
            $allowedFlows,
            true
        )) {
            throw new DomainException(
                'Kategori tidak sesuai dengan jenis transaksi.'
            );
        }

        return $category;
    }

    private function defaultAdminFeeCategory(
        User $user
    ): FinanceCategory {
        return FinanceCategory::query()
            ->where('user_id', $user->id)
            ->where('name', 'Biaya Admin')
            ->where('flow_type', FinanceFlowType::Expense->value)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createPostedTransaction(
        User $user,
        TransactionType $type,
        array $data,
        string $defaultDescription
    ): Transaction {
        return $user->transactions()->create([
            'type' => $type,
            'status' => TransactionStatus::Posted,
            'source' => $this->transactionSource(
                $data['source'] ?? TransactionSource::Manual
            ),
            'occurred_at' => $this->normalizeOccurredAt(
                $user,
                $data['occurred_at'] ?? null
            ),
            'description' => $this->nullableString(
                $data['description'] ?? $defaultDescription
            ),
            'counterparty' => $this->nullableString(
                $data['counterparty'] ?? null
            ),
            'reference_number' => $this->nullableString(
                $data['reference_number'] ?? null
            ),
            'notes' => $this->nullableString(
                $data['notes'] ?? null
            ),
            'metadata' => is_array($data['metadata'] ?? null)
                ? $data['metadata']
                : null,
            'posted_at' => now(),
            'cancelled_at' => null,
        ]);
    }

    private function normalizeOccurredAt(
        User $user,
        mixed $value
    ): CarbonImmutable {
        $userTimezone = $user->preference()
            ->value('timezone')
            ?? config('laras.defaults.timezone');

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)
                ->setTimezone(config('app.timezone'));
        }

        return CarbonImmutable::parse(
            $value ?? 'now',
            $userTimezone
        )->setTimezone(config('app.timezone'));
    }

    private function transactionSource(
        mixed $value
    ): TransactionSource {
        if ($value instanceof TransactionSource) {
            return $value;
        }

        try {
            return TransactionSource::from((string) $value);
        } catch (ValueError) {
            throw new DomainException(
                'Sumber transaksi tidak valid.'
            );
        }
    }

    private function ensureBalanceIsSufficient(
        Account $account,
        string $delta
    ): void {
        $newBalance = bcadd(
            $account->cached_balance,
            $delta,
            2
        );

        if (bccomp($newBalance, '0.00', 2) < 0) {
            throw new DomainException(
                sprintf(
                    'Saldo rekening %s tidak mencukupi.',
                    $account->name
                )
            );
        }
    }

    private function applyBalanceDelta(
        Account $account,
        string $delta,
        bool $allowNegative = false
    ): void {
        $newBalance = bcadd(
            $account->cached_balance,
            $delta,
            2
        );

        if (
            ! $allowNegative
            && bccomp($newBalance, '0.00', 2) < 0
        ) {
            throw new DomainException(
                sprintf(
                    'Saldo rekening %s tidak mencukupi.',
                    $account->name
                )
            );
        }

        $account->forceFill([
            'cached_balance' => $newBalance,
        ])->save();
    }

    private function normalizePositiveMoney(
        mixed $value,
        string $label
    ): string {
        $normalized = $this->normalizeMoney(
            $value,
            $label
        );

        if (bccomp($normalized, '0.00', 2) <= 0) {
            throw new DomainException(
                $label.' harus lebih besar dari nol.'
            );
        }

        return $normalized;
    }

    private function normalizeNonNegativeMoney(
        mixed $value,
        string $label
    ): string {
        $normalized = $this->normalizeMoney(
            $value,
            $label
        );

        if (bccomp($normalized, '0.00', 2) < 0) {
            throw new DomainException(
                $label.' tidak boleh kurang dari nol.'
            );
        }

        return $normalized;
    }

    private function normalizeMoney(
        mixed $value,
        string $label
    ): string {
        $rawValue = trim((string) $value);

        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $rawValue
            ) !== 1
        ) {
            throw new DomainException(
                $label.' harus berupa nominal yang valid.'
            );
        }

        $normalized = bcadd(
            $rawValue,
            '0',
            2
        );

        if (
            bccomp(
                $normalized,
                '9999999999999999.99',
                2
            ) > 0
        ) {
            throw new DomainException(
                $label.' melebihi batas yang diperbolehkan.'
            );
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === ''
            ? null
            : $normalized;
    }
}
