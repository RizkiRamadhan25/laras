<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AccountDeletionService
{
    public function delete(User $user): void
    {
        $userId = (int) $user->getKey();

        $profilePhotoPath = $user->getRawOriginal(
            'profile_photo_path'
        );

        DB::transaction(
            function () use ($userId): void {
                $lockedUser = User::query()
                    ->withTrashed()
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->deleteAuthenticationData(
                    userId: $userId,
                    email: (string) $lockedUser->email,
                    notifiableType: $lockedUser->getMorphClass()
                );

                /*
                 * Data dengan foreign key RESTRICT harus dihapus
                 * sebelum rekening dan kategori keuangan.
                 */
                $this->deleteBudgetData($userId);
                $this->deleteSubscriptionData($userId);
                $this->deleteTransactionData($userId);

                $this->deleteDirectUserData($userId);

                /*
                 * Setelah seluruh data yang bergantung pada rekening
                 * dan kategori dihapus, kedua parent tersebut aman
                 * untuk dihapus permanen.
                 */
                DB::table('accounts')
                    ->where('user_id', $userId)
                    ->delete();

                DB::table('finance_categories')
                    ->where('user_id', $userId)
                    ->delete();

                $deletedRows = DB::table('users')
                    ->where('id', $userId)
                    ->delete();

                if ($deletedRows !== 1) {
                    throw new RuntimeException(
                        'Akun gagal dihapus secara permanen.'
                    );
                }
            },
            3
        );

        /*
         * Operasi filesystem tidak dapat di-rollback bersama database.
         * Karena itu, file dibersihkan setelah transaksi database sukses.
         */
        $this->deleteProfileFiles(
            userId: $userId,
            profilePhotoPath: is_string($profilePhotoPath)
                ? $profilePhotoPath
                : null
        );
    }

    private function deleteAuthenticationData(
        int $userId,
        string $email,
        string $notifiableType
    ): void {
        DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();

        DB::table('notifications')
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $userId)
            ->delete();

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }

    private function deleteBudgetData(int $userId): void
    {
        DB::table('budget_alert_events')
            ->whereIn(
                'budget_period_id',
                function (Builder $periodQuery) use ($userId): void {
                    $periodQuery
                        ->select('budget_periods.id')
                        ->from('budget_periods')
                        ->whereIn(
                            'budget_id',
                            function (Builder $budgetQuery) use ($userId): void {
                                $budgetQuery
                                    ->select('budgets.id')
                                    ->from('budgets')
                                    ->where('user_id', $userId);
                            }
                        );
                }
            )
            ->delete();

        DB::table('budget_periods')
            ->whereIn(
                'budget_id',
                function (Builder $query) use ($userId): void {
                    $query
                        ->select('budgets.id')
                        ->from('budgets')
                        ->where('user_id', $userId);
                }
            )
            ->delete();

        DB::table('budgets')
            ->where('user_id', $userId)
            ->delete();
    }

    private function deleteSubscriptionData(int $userId): void
    {
        DB::table('subscription_billings')
            ->where('user_id', $userId)
            ->delete();

        DB::table('subscriptions')
            ->where('user_id', $userId)
            ->delete();
    }

    private function deleteTransactionData(int $userId): void
    {
        DB::table('transaction_entries')
            ->whereIn(
                'transaction_id',
                function (Builder $query) use ($userId): void {
                    $query
                        ->select('transactions.id')
                        ->from('transactions')
                        ->where('user_id', $userId);
                }
            )
            ->delete();

        DB::table('transactions')
            ->where('user_id', $userId)
            ->delete();
    }

    private function deleteDirectUserData(int $userId): void
    {
        $tables = [
            'passkeys',
            'recommendation_interactions',
            'security_events',
            'activities',
            'user_preferences',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->where('user_id', $userId)
                ->delete();
        }
    }

    private function deleteProfileFiles(
        int $userId,
        ?string $profilePhotoPath
    ): void {
        $disk = Storage::disk('public');

        try {
            if (
                filled($profilePhotoPath)
                && $disk->exists($profilePhotoPath)
                && ! $disk->delete($profilePhotoPath)
            ) {
                Log::warning(
                    'Foto profil pengguna gagal dihapus setelah penghapusan akun.',
                    [
                        'user_id' => $userId,
                        'path' => $profilePhotoPath,
                    ]
                );
            }

            $directory = 'profile-photos/'.$userId;

            if (
                $disk->directoryExists($directory)
                && ! $disk->deleteDirectory($directory)
            ) {
                Log::warning(
                    'Direktori foto profil pengguna gagal dihapus setelah penghapusan akun.',
                    [
                        'user_id' => $userId,
                        'directory' => $directory,
                    ]
                );
            }
        } catch (Throwable $exception) {
            Log::warning(
                'Pembersihan file setelah penghapusan akun mengalami kegagalan.',
                [
                    'user_id' => $userId,
                    'path' => $profilePhotoPath,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }
}
