<?php

namespace App\Console\Commands;

use App\Services\SubscriptionAutomationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature(
    'subscriptions:process
    {--date= : Simulasikan tanggal tertentu dalam format Y-m-d}
    {--force : Abaikan waktu penagihan dan proses tagihan yang sudah jatuh tempo}'
)]
#[Description(
    'Process subscription reminders and recurring billings'
)]
class ProcessSubscriptions extends Command
{
    public function handle(
        SubscriptionAutomationService $automation
    ): int {
        $dateOption = $this->option('date');

        $date = is_string($dateOption)
            && trim($dateOption) !== ''
                ? trim($dateOption)
                : null;

        if (
            $date !== null
            && ! $this->validDate($date)
        ) {
            $this->error(
                'Opsi --date harus menggunakan format Y-m-d yang valid.'
            );

            return self::FAILURE;
        }

        $summary = $automation->run(
            date: $date,
            force: (bool) $this->option('force')
        );

        $this->components->info(
            'Pemrosesan langganan selesai.'
        );

        $this->table(
            [
                'Keterangan',
                'Jumlah',
            ],
            [
                [
                    'Langganan diperiksa',
                    $summary[
                        'subscriptions_checked'
                    ],
                ],
                [
                    'Pengingat dikirim',
                    $summary[
                        'reminders_sent'
                    ],
                ],
                [
                    'Tagihan berhasil',
                    $summary[
                        'billings_posted'
                    ],
                ],
                [
                    'Tagihan gagal',
                    $summary[
                        'billings_failed'
                    ],
                ],
                [
                    'Menunggu pencatatan manual',
                    $summary['manual_due'],
                ],
                [
                    'Error sistem',
                    $summary['errors'],
                ],
            ]
        );

        return $summary['errors'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function validDate(
        string $date
    ): bool {
        try {
            $parsed =
                CarbonImmutable::createFromFormat(
                    'Y-m-d',
                    $date
                );
        } catch (Throwable) {
            return false;
        }

        return $parsed !== false
            && $parsed->format('Y-m-d')
                === $date;
    }
}
