<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class PersonalDataExportService
{
    /**
     * @return array{path: string, filename: string}
     */
    public function createArchive(
        User $user
    ): array {
        $user->loadMissing('preference');

        $exportId = (string) Str::uuid();

        $exportRoot = storage_path(
            'app/private/exports'
        );

        $workingDirectory = $exportRoot
            .DIRECTORY_SEPARATOR
            .$exportId;

        $zipPath = $exportRoot
            .DIRECTORY_SEPARATOR
            .$exportId
            .'.zip';

        File::ensureDirectoryExists(
            $workingDirectory
        );

        try {
            $datasetCounts = [];

            foreach (
                $this->datasets($user)
                as $relativePath => $query
            ) {
                $absolutePath = $workingDirectory
                    .DIRECTORY_SEPARATOR
                    .str_replace(
                        '/',
                        DIRECTORY_SEPARATOR,
                        $relativePath
                    );

                File::ensureDirectoryExists(
                    dirname($absolutePath)
                );

                $datasetCounts[$relativePath] =
                    $this->writeQueryAsJson(
                        $absolutePath,
                        $query
                    );
            }

            $profilePhotoEntry =
                $this->copyProfilePhoto(
                    $user,
                    $workingDirectory
                );

            $manifestPath = $workingDirectory
                .DIRECTORY_SEPARATOR
                .'manifest.json';

            File::put(
                $manifestPath,
                json_encode(
                    [
                        'application' =>
                            config(
                                'app.name',
                                'Laras'
                            ),

                        'archive_version' => 2,

                        'generated_at' =>
                            now()->toIso8601String(),

                        'timezone' =>
                            $user->preference
                                ?->timezone
                            ?? config(
                                'laras.defaults.timezone',
                                'Asia/Jakarta'
                            ),

                        'profile' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,

                            'email_verified_at' =>
                                $user->getRawOriginal(
                                    'email_verified_at'
                                ),

                            'onboarding_completed_at' =>
                                $user->getRawOriginal(
                                    'onboarding_completed_at'
                                ),

                            'is_active' =>
                                (bool) $user->is_active,

                            'profile_photo_file' =>
                                $profilePhotoEntry,

                            'created_at' =>
                                $user->getRawOriginal(
                                    'created_at'
                                ),

                            'updated_at' =>
                                $user->getRawOriginal(
                                    'updated_at'
                                ),
                        ],

                        'datasets' =>
                            $datasetCounts,

                        'excluded_fields' => [
                            'password',
                            'remember_token',
                            'session_payload',
                            'password_reset_token',
                        ],
                    ],
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                )
            );

            $this->createZip(
                $workingDirectory,
                $zipPath
            );

            $filename = sprintf(
                'laras-data-%s.zip',
                now()
                    ->setTimezone(
                        $user->preference
                            ?->timezone
                        ?? config(
                            'laras.defaults.timezone',
                            'Asia/Jakarta'
                        )
                    )
                    ->format('Ymd-His')
            );

            return [
                'path' => $zipPath,
                'filename' => $filename,
            ];
        } catch (Throwable $exception) {
            File::delete($zipPath);

            throw $exception;
        } finally {
            File::deleteDirectory(
                $workingDirectory
            );
        }
    }

    /**
     * @return array<string, Builder|null>
     */
    private function datasets(
        User $user
    ): array {
        $userId = (int) $user->id;

        return [
            'preferences/preferences.json' =>
                $this->queryByUser(
                    'user_preferences',
                    $userId
                ),

            'onboarding/progress.json' =>
                $this->firstAvailableUserQuery(
                    [
                        'onboarding_progress',
                        'onboarding_progresses',
                    ],
                    $userId
                ),

            'finance/categories.json' =>
                $this->queryByUser(
                    'finance_categories',
                    $userId
                ),

            'finance/accounts.json' =>
                $this->queryByUser(
                    'accounts',
                    $userId
                ),

            'finance/account-balance-snapshots.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'account_balance_snapshots',
                    foreignKey: 'account_id',
                    parentTable: 'accounts',
                    userId: $userId
                ),

            'finance/transactions.json' =>
                $this->queryByUser(
                    'transactions',
                    $userId
                ),

            'finance/transaction-entries.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'transaction_entries',
                    foreignKey: 'transaction_id',
                    parentTable: 'transactions',
                    userId: $userId
                ),

            'finance/transaction-attachments.json' =>
                $this->queryByUser(
                    'transaction_attachments',
                    $userId
                ),

            'finance/budgets.json' =>
                $this->queryByUser(
                    'budgets',
                    $userId
                ),

            'finance/budget-periods.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'budget_periods',
                    foreignKey: 'budget_id',
                    parentTable: 'budgets',
                    userId: $userId
                ),

            'finance/budget-alert-events.json' =>
                $this->budgetAlertEventsQuery(
                    $userId
                ),

            'finance/scan-documents.json' =>
                $this->queryByUser(
                    'scan_documents',
                    $userId
                ),

            'finance/scan-extractions.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'scan_extractions',
                    foreignKey: 'scan_document_id',
                    parentTable: 'scan_documents',
                    userId: $userId
                ),

            'activities/items.json' =>
                $this->queryByUser(
                    'activities',
                    $userId
                ),

            'activities/status-histories.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'activity_status_histories',
                    foreignKey: 'activity_id',
                    parentTable: 'activities',
                    userId: $userId
                ),

            'activities/priority-histories.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'activity_priority_histories',
                    foreignKey: 'activity_id',
                    parentTable: 'activities',
                    userId: $userId
                ),

            'subscriptions/items.json' =>
                $this->queryByUser(
                    'subscriptions',
                    $userId
                ),

            'subscriptions/billings.json' =>
                $this->queryWhereParentBelongsToUser(
                    table: 'subscription_billings',
                    foreignKey: 'subscription_id',
                    parentTable: 'subscriptions',
                    userId: $userId
                ),

            'recommendations/interactions.json' =>
                $this->queryByUser(
                    'recommendation_interactions',
                    $userId
                ),

            'security/events.json' =>
                $this->queryByUser(
                    'security_events',
                    $userId
                ),

            'notifications/items.json' =>
                $this->notificationsQuery(
                    $user
                ),
        ];
    }

    private function queryByUser(
        string $table,
        int $userId
    ): ?Builder {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn(
                $table,
                'user_id'
            )
        ) {
            return null;
        }

        return DB::table($table)
            ->where('user_id', $userId);
    }

    private function queryWhereParentBelongsToUser(
        string $table,
        string $foreignKey,
        string $parentTable,
        int $userId
    ): ?Builder {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable(
                $parentTable
            )
            || ! Schema::hasColumn(
                $table,
                $foreignKey
            )
            || ! Schema::hasColumn(
                $parentTable,
                'user_id'
            )
        ) {
            return null;
        }

        return DB::table($table)
            ->whereIn(
                $foreignKey,
                function (
                    Builder $query
                ) use (
                    $parentTable,
                    $userId
                ): void {
                    $query
                        ->select('id')
                        ->from($parentTable)
                        ->where(
                            'user_id',
                            $userId
                        );
                }
            );
    }

    private function budgetAlertEventsQuery(
        int $userId
    ): ?Builder {
        if (
            ! Schema::hasTable(
                'budget_alert_events'
            )
            || ! Schema::hasTable(
                'budget_periods'
            )
            || ! Schema::hasTable('budgets')
        ) {
            return null;
        }

        return DB::table(
            'budget_alert_events'
        )->whereIn(
            'budget_period_id',
            function (
                Builder $periodQuery
            ) use ($userId): void {
                $periodQuery
                    ->select(
                        'budget_periods.id'
                    )
                    ->from(
                        'budget_periods'
                    )
                    ->whereIn(
                        'budget_id',
                        function (
                            Builder $budgetQuery
                        ) use ($userId): void {
                            $budgetQuery
                                ->select(
                                    'budgets.id'
                                )
                                ->from('budgets')
                                ->where(
                                    'user_id',
                                    $userId
                                );
                        }
                    );
            }
        );
    }

    private function notificationsQuery(
        User $user
    ): ?Builder {
        if (
            ! Schema::hasTable(
                'notifications'
            )
        ) {
            return null;
        }

        return DB::table('notifications')
            ->where(
                'notifiable_type',
                $user->getMorphClass()
            )
            ->where(
                'notifiable_id',
                $user->id
            );
    }

    /**
     * @param array<int, string> $tables
     */
    private function firstAvailableUserQuery(
        array $tables,
        int $userId
    ): ?Builder {
        foreach ($tables as $table) {
            $query = $this->queryByUser(
                $table,
                $userId
            );

            if ($query !== null) {
                return $query;
            }
        }

        return null;
    }

    private function writeQueryAsJson(
        string $path,
        ?Builder $query
    ): int {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException(
                'File ekspor tidak dapat dibuat.'
            );
        }

        $count = 0;

        try {
            fwrite($handle, "[\n");

            if ($query !== null) {
                $table = $query->from;

                if (
                    is_string($table)
                    && Schema::hasColumn(
                        $table,
                        'id'
                    )
                ) {
                    $query->orderBy('id');
                }

                foreach ($query->cursor() as $row) {
                    if ($count > 0) {
                        fwrite($handle, ",\n");
                    }

                    fwrite(
                        $handle,
                        json_encode(
                            (array) $row,
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                            | JSON_THROW_ON_ERROR
                        )
                    );

                    $count++;
                }
            }

            fwrite($handle, "\n]\n");
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function copyProfilePhoto(
        User $user,
        string $workingDirectory
    ): ?string {
        $photoPath = $user
            ->profile_photo_path;

        if (
            blank($photoPath)
            || ! Storage::disk('public')
                ->exists($photoPath)
        ) {
            return null;
        }

        $extension = strtolower(
            pathinfo(
                $photoPath,
                PATHINFO_EXTENSION
            )
        );

        $extension = preg_match(
            '/^[a-z0-9]{2,5}$/',
            $extension
        )
            ? $extension
            : 'bin';

        $relativePath = 'files/profile-photo.'
            .$extension;

        $destination = $workingDirectory
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        File::ensureDirectoryExists(
            dirname($destination)
        );

        $source = Storage::disk('public')
            ->path($photoPath);

        if (! File::copy($source, $destination)) {
            throw new RuntimeException(
                'Foto profil gagal ditambahkan ke arsip.'
            );
        }

        return $relativePath;
    }

    private function createZip(
        string $workingDirectory,
        string $zipPath
    ): void {
        File::ensureDirectoryExists(
            dirname($zipPath)
        );

        $zip = new ZipArchive();

        $opened = $zip->open(
            $zipPath,
            ZipArchive::CREATE
            | ZipArchive::OVERWRITE
        );

        if ($opened !== true) {
            throw new RuntimeException(
                'Arsip ZIP gagal dibuat.'
            );
        }

        try {
            foreach (
                File::allFiles(
                    $workingDirectory
                ) as $file
            ) {
                $relativePath = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    $file->getRelativePathname()
                );

                if (
                    ! $zip->addFile(
                        $file->getPathname(),
                        $relativePath
                    )
                ) {
                    throw new RuntimeException(
                        'File gagal dimasukkan ke arsip ZIP.'
                    );
                }
            }
        } finally {
            $closed = $zip->close();
        }

        if (! $closed) {
            throw new RuntimeException(
                'Arsip ZIP gagal diselesaikan.'
            );
        }
    }
}
