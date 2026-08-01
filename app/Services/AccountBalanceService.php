<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\TransactionEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccountBalanceService
{
    public function calculate(Account $account): string
    {
        $entriesTotal = TransactionEntry::query()
            ->where('account_id', $account->id)
            ->whereHas(
                'transaction',
                function (Builder $query): void {
                    $query->where(
                        'status',
                        TransactionStatus::Posted->value
                    );
                }
            )
            ->sum('amount');

        $normalizedEntriesTotal = bcadd(
            (string) $entriesTotal,
            '0',
            2
        );

        return bcadd(
            $account->initial_balance,
            $normalizedEntriesTotal,
            2
        );
    }

    public function reconcile(Account $account): Account
    {
        return DB::transaction(
            function () use ($account): Account {
                $lockedAccount = Account::query()
                    ->lockForUpdate()
                    ->findOrFail($account->id);

                $calculatedBalance = $this->calculate(
                    $lockedAccount
                );

                $lockedAccount->forceFill([
                    'cached_balance' => $calculatedBalance,
                ])->save();

                return $lockedAccount->refresh();
            },
            3
        );
    }
}
