<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Services\BudgetUsageSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBudgetUsageCommand extends Command
{
    protected $signature = 'budgets:sync-usage
        {--user= : Batasi sinkronisasi berdasarkan ID pengguna}
        {--budget= : Sinkronkan hanya satu ID anggaran}';

    protected $description =
        'Hitung ulang penggunaan semua anggaran aktif dari ledger transaksi tercatat.';

    public function handle(
        BudgetUsageSyncService $syncService
    ): int {
        $userId = $this->numericOption('user');

        if ($userId === false) {
            return self::FAILURE;
        }

        $budgetId = $this->numericOption('budget');

        if ($budgetId === false) {
            return self::FAILURE;
        }

        $query = Budget::query()
            ->where('is_active', true)
            ->with([
                'user.preference',
                'periods',
            ]);

        if (is_int($userId)) {
            $query->where(
                'user_id',
                $userId
            );
        }

        if (is_int($budgetId)) {
            $query->whereKey(
                $budgetId
            );
        }

        $budgetCount = (clone $query)->count();

        if ($budgetCount === 0) {
            $this->warn(
                'Tidak ada anggaran aktif yang sesuai dengan filter.'
            );

            return self::SUCCESS;
        }

        $this->info(
            sprintf(
                'Menyinkronkan %d anggaran aktif...',
                $budgetCount
            )
        );

        $progress = $this->output
            ->createProgressBar(
                $budgetCount
            );

        $progress->start();

        $syncedPeriods = 0;

        $query
            ->orderBy('id')
            ->chunkById(
                100,
                function ($budgets) use (
                    $syncService,
                    $progress,
                    &$syncedPeriods
                ): void {
                    foreach ($budgets as $budget) {
                        $syncedPeriods += DB::transaction(
                            fn (): int => $syncService
                                ->syncAllRelevantPeriods(
                                    $budget
                                ),
                            3
                        );

                        $progress->advance();
                    }
                }
            );

        $progress->finish();

        $this->newLine(2);

        $this->info(
            sprintf(
                'Selesai. %d anggaran dan %d periode telah disinkronkan.',
                $budgetCount,
                $syncedPeriods
            )
        );

        return self::SUCCESS;
    }

    private function numericOption(
        string $name
    ): int|null|false {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return null;
        }

        if (
            ! is_numeric($value)
            || (int) $value < 1
        ) {
            $this->error(
                sprintf(
                    'Opsi --%s harus berupa ID positif.',
                    $name
                )
            );

            return false;
        }

        return (int) $value;
    }
}
