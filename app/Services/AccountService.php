<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use App\Enums\SubscriptionStatus;

class AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Account
    {
        return DB::transaction(function () use ($user, $data): Account {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $initialBalance = $this->normalizeMoney(
                $data['initial_balance']
            );

            $lastSortOrder = (int) $lockedUser
                ->accounts()
                ->withTrashed()
                ->max('sort_order');

            $type = AccountType::from($data['type']);

            return $lockedUser->accounts()->create([
                'name' => $data['name'],
                'type' => $type,
                'institution' => $data['institution'] ?? null,
                'currency_code' => $lockedUser->preference()
                    ->value('currency_code') ?? 'IDR',
                'initial_balance' => $initialBalance,
                'cached_balance' => $initialBalance,
                'account_number_last_four' => $data['account_number_last_four'] ?? null,
                'color' => $data['color'],
                'icon' => $this->accountIcon($type),
                'is_active' => true,
                'sort_order' => $lastSortOrder + 1,
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        User $user,
        int $accountId,
        array $data
    ): Account {
        return DB::transaction(function () use (
            $user,
            $accountId,
            $data
        ): Account {
            $account = Account::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->findOrFail($accountId);

            $newInitialBalance = $this->normalizeMoney(
                $data['initial_balance']
            );

            /*
             * Current balance is shifted by the same difference as the
             * initial balance. Existing transaction effects remain intact.
             */
            $initialBalanceDifference = bcsub(
                $newInitialBalance,
                $account->initial_balance,
                2
            );

            $newCachedBalance = bcadd(
                $account->cached_balance,
                $initialBalanceDifference,
                2
            );

            $type = AccountType::from($data['type']);

            $account->update([
                'name' => $data['name'],
                'type' => $type,
                'institution' => $data['institution'] ?? null,
                'initial_balance' => $newInitialBalance,
                'cached_balance' => $newCachedBalance,
                'account_number_last_four' => $data['account_number_last_four'] ?? null,
                'color' => $data['color'],
                'icon' => $this->accountIcon($type),
            ]);

            return $account->refresh();
        }, 3);
    }

    public function archive(User $user, int $accountId): void
    {
        DB::transaction(function () use ($user, $accountId): void {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $account = Account::query()
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->findOrFail($accountId);

            $activeAccountCount = $lockedUser
                ->accounts()
                ->where('is_active', true)
                ->count();

            if ($activeAccountCount <= 1) {
                throw new DomainException(
                    'Minimal satu rekening harus tetap aktif.'
                );
            }

            $blockingSubscriptionCount = $account
                ->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::Paused->value,
                ])
                ->lockForUpdate()
                ->count();

            if ($blockingSubscriptionCount > 0) {
                throw new DomainException(
                    "Rekening masih digunakan oleh {$blockingSubscriptionCount} langganan aktif atau dijeda. Pindahkan rekening langganan, jeda, atau batalkan langganan terlebih dahulu."
                );
            }

            $account->forceFill([
                'is_active' => false,
            ])->save();

            $account->delete();
        }, 3);
    }

    public function restore(User $user, int $accountId): void
    {
        DB::transaction(function () use ($user, $accountId): void {
            User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $account = Account::query()
                ->onlyTrashed()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->findOrFail($accountId);

            $account->restore();

            $account->forceFill([
                'is_active' => true,
            ])->save();
        }, 3);
    }

    /**
     * @return list<int>
     */
    public function move(
        User $user,
        int $accountId,
        string $direction
    ): array {
        return DB::transaction(function () use (
            $user,
            $accountId,
            $direction
        ): array {
            User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $accounts = Account::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $currentIndex = $accounts->search(
                fn (Account $account): bool =>
                    $account->id === $accountId
            );

            if ($currentIndex === false) {
                abort(404);
            }

            $targetIndex = $direction === 'up'
                ? $currentIndex - 1
                : $currentIndex + 1;

            if ($accounts->has($targetIndex)) {
                $currentAccount = $accounts->get($currentIndex);
                $targetAccount = $accounts->get($targetIndex);

                $currentSortOrder = $currentAccount->sort_order;
                $targetSortOrder = $targetAccount->sort_order;

                $currentAccount->update([
                    'sort_order' => $targetSortOrder,
                ]);

                $targetAccount->update([
                    'sort_order' => $currentSortOrder,
                ]);
            }

            return Account::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int => (int) $id
                )
                ->values()
                ->all();
        }, 3);
    }

    private function normalizeMoney(mixed $value): string
    {
        return bcadd((string) $value, '0', 2);
    }

    private function accountIcon(AccountType $type): string
    {
        return match ($type) {
            AccountType::Bank => 'landmark',
            AccountType::EWallet => 'smartphone',
            AccountType::Cash => 'wallet',
            AccountType::Investment => 'chart-no-axes-combined',
            AccountType::Other => 'circle-dollar-sign',
        };
    }
}
