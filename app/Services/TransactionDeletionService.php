<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TransactionDeletionService
{
    public function __construct(
        private readonly AccountBalanceService $accountBalanceService,
        private readonly BudgetUsageSyncService $budgetUsageSyncService
    ) {}

    public function deletePermanently(
        User $user,
        int $transactionId
    ): void {
        $user->loadMissing('preference');

        DB::transaction(function () use (
            $user,
            $transactionId
        ): void {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $transaction = Transaction::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->findOrFail($transactionId);

            if (
                $transaction->status
                !== TransactionStatus::Cancelled
            ) {
                throw new DomainException(
                    'Batalkan transaksi terlebih dahulu sebelum menghapusnya permanen.'
                );
            }

            $entries = TransactionEntry::query()
                ->where(
                    'transaction_id',
                    $transaction->id
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $accountIds = $entries
                ->pluck('account_id')
                ->map(
                    static fn (mixed $accountId): int => (int) $accountId
                )
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

            if (
                $accounts->count()
                !== count($accountIds)
            ) {
                throw (new ModelNotFoundException)
                    ->setModel(
                        Account::class,
                        $accountIds
                    );
            }

            $categoryIds = $entries
                ->pluck('finance_category_id')
                ->filter()
                ->map(
                    static fn (mixed $categoryId): int => (int) $categoryId
                )
                ->unique()
                ->sort()
                ->values();

            $timezone = $user->preference?->timezone
                ?? config('laras.defaults.timezone');

            $localDate = CarbonImmutable::instance(
                $transaction->occurred_at
            )
                ->setTimezone($timezone)
                ->startOfDay();

            /*
             * Force delete pada parent menghapus seluruh ledger
             * entry melalui foreign key cascade. Tidak ada entry
             * transfer yang dapat tertinggal atau dihapus parsial.
             */
            $transaction->forceDelete();

            foreach ($accounts as $account) {
                $calculatedBalance =
                    $this->accountBalanceService
                        ->calculate($account);

                $account->forceFill([
                    'cached_balance' => $calculatedBalance,
                ])->save();
            }

            foreach ($categoryIds as $categoryId) {
                $this->budgetUsageSyncService
                    ->syncActiveBudgetsForCategoryDate(
                        $user,
                        $categoryId,
                        $localDate
                    );
            }
        }, 3);
    }
}
