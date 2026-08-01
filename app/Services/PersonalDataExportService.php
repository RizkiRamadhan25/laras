<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PersonalDataExportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $accounts = $this->rowsByUser(
            'accounts',
            $user->id
        );

        $accountIds = $this->ids(
            $accounts
        );

        $transactions = $this->rowsByUser(
            'transactions',
            $user->id
        );

        $transactionIds = $this->ids(
            $transactions
        );

        $activities = $this->rowsByUser(
            'activities',
            $user->id
        );

        $activityIds = $this->ids(
            $activities
        );

        $subscriptions = $this->rowsByUser(
            'subscriptions',
            $user->id
        );

        $subscriptionIds = $this->ids(
            $subscriptions
        );

        $budgets = $this->rowsByUser(
            'budgets',
            $user->id
        );

        $budgetIds = $this->ids(
            $budgets
        );

        $scanDocuments = $this->rowsByUser(
            'scan_documents',
            $user->id
        );

        $scanDocumentIds = $this->ids(
            $scanDocuments
        );

        return [
            'meta' => [
                'application' =>
                    config('app.name', 'Laras'),

                'archive_version' => 1,

                'generated_at' =>
                    now()->toIso8601String(),

                'timezone' =>
                    $user->preference?->timezone
                    ?? config(
                        'app.timezone',
                        'Asia/Jakarta'
                    ),
            ],

            /*
             * Password, remember_token, dan
             * token sesi tidak diekspor.
             */
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

                'has_profile_photo' =>
                    filled(
                        $user
                            ->profile_photo_path
                    ),

                'created_at' =>
                    $user->getRawOriginal(
                        'created_at'
                    ),

                'updated_at' =>
                    $user->getRawOriginal(
                        'updated_at'
                    ),
            ],

            'preferences' => $this->first(
                $this->rowsByUser(
                    'user_preferences',
                    $user->id
                )
            ),

            'onboarding' =>
                $this->firstAvailableUserRow(
                    [
                        'onboarding_progress',
                        'onboarding_progresses',
                    ],
                    $user->id
                ),

            'finance' => [
                'categories' =>
                    $this->rowsByUser(
                        'finance_categories',
                        $user->id
                    ),

                'accounts' => $accounts,

                'account_balance_snapshots' =>
                    $this->rowsWhereIn(
                        'account_balance_snapshots',
                        'account_id',
                        $accountIds
                    ),

                'transactions' =>
                    $transactions,

                'transaction_entries' =>
                    $this->rowsWhereIn(
                        'transaction_entries',
                        'transaction_id',
                        $transactionIds
                    ),

                'transaction_attachments' =>
                    $this->rowsByUser(
                        'transaction_attachments',
                        $user->id
                    ),

                'budgets' => $budgets,

                'budget_periods' =>
                    $this->rowsWhereIn(
                        'budget_periods',
                        'budget_id',
                        $budgetIds
                    ),

                'scan_documents' =>
                    $scanDocuments,

                'scan_extractions' =>
                    $this->rowsWhereIn(
                        'scan_extractions',
                        'scan_document_id',
                        $scanDocumentIds
                    ),
            ],

            'activities' => [
                'items' => $activities,

                'status_histories' =>
                    $this->rowsWhereIn(
                        'activity_status_histories',
                        'activity_id',
                        $activityIds
                    ),

                'priority_histories' =>
                    $this->rowsWhereIn(
                        'activity_priority_histories',
                        'activity_id',
                        $activityIds
                    ),
            ],

            'subscriptions' => [
                'items' => $subscriptions,

                'billings' =>
                    $this->rowsWhereIn(
                        'subscription_billings',
                        'subscription_id',
                        $subscriptionIds
                    ),
            ],

            'recommendations' => [
                'interactions' =>
                    $this->rowsByUser(
                        'recommendation_interactions',
                        $user->id
                    ),
            ],

            'security' => [
                'events' =>
                    $this->rowsByUser(
                        'security_events',
                        $user->id
                    ),
            ],

            'notifications' =>
                $this->notificationRows(
                    $user
                ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsByUser(
        string $table,
        int $userId
    ): array {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn(
                $table,
                'user_id'
            )
        ) {
            return [];
        }

        return $this->fetch(
            $table,
            function (
                Builder $query
            ) use ($userId): void {
                $query->where(
                    'user_id',
                    $userId
                );
            }
        );
    }

    /**
     * @param array<int, int|string> $values
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowsWhereIn(
        string $table,
        string $column,
        array $values
    ): array {
        if (
            $values === []
            || ! Schema::hasTable($table)
            || ! Schema::hasColumn(
                $table,
                $column
            )
        ) {
            return [];
        }

        return $this->fetch(
            $table,
            function (
                Builder $query
            ) use (
                $column,
                $values
            ): void {
                $query->whereIn(
                    $column,
                    $values
                );
            }
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notificationRows(
        User $user
    ): array {
        if (
            ! Schema::hasTable(
                'notifications'
            )
        ) {
            return [];
        }

        return $this->fetch(
            'notifications',
            function (
                Builder $query
            ) use ($user): void {
                $query
                    ->where(
                        'notifiable_type',
                        User::class
                    )
                    ->where(
                        'notifiable_id',
                        $user->id
                    );
            }
        );
    }

    /**
     * @param array<int, string> $tables
     *
     * @return array<string, mixed>|null
     */
    private function firstAvailableUserRow(
        array $tables,
        int $userId
    ): ?array {
        foreach ($tables as $table) {
            $rows = $this->rowsByUser(
                $table,
                $userId
            );

            if ($rows !== []) {
                return $rows[0];
            }
        }

        return null;
    }

    /**
     * @param Closure(Builder): void $scope
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetch(
        string $table,
        Closure $scope
    ): array {
        $query = DB::table($table);

        $scope($query);

        if (
            Schema::hasColumn(
                $table,
                'id'
            )
        ) {
            $query->orderBy('id');
        }

        return $query
            ->get()
            ->map(
                fn (object $row): array =>
                    (array) $row
            )
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, int|string>
     */
    private function ids(array $rows): array
    {
        return array_values(
            array_filter(
                array_column(
                    $rows,
                    'id'
                ),
                fn (mixed $id): bool =>
                    $id !== null
            )
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    private function first(
        array $rows
    ): ?array {
        return $rows[0] ?? null;
    }
}
