<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'budgets',
            function (Blueprint $table): void {
                $table->index(
                    [
                        'user_id',
                        'is_active',
                        'start_date',
                        'end_date',
                    ],
                    'budgets_user_active_dates_index'
                );
            }
        );

        Schema::table(
            'budget_periods',
            function (Blueprint $table): void {
                $table->index(
                    [
                        'budget_id',
                        'status',
                        'period_start',
                        'period_end',
                    ],
                    'budget_periods_budget_status_dates_index'
                );
            }
        );

        Schema::table(
            'notifications',
            function (Blueprint $table): void {
                $table->index(
                    [
                        'notifiable_type',
                        'notifiable_id',
                        'created_at',
                    ],
                    'notifications_notifiable_created_index'
                );

                $table->index(
                    [
                        'notifiable_type',
                        'notifiable_id',
                        'read_at',
                    ],
                    'notifications_notifiable_read_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'notifications',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'notifications_notifiable_created_index'
                );

                $table->dropIndex(
                    'notifications_notifiable_read_index'
                );
            }
        );

        Schema::table(
            'budget_periods',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'budget_periods_budget_status_dates_index'
                );
            }
        );

        Schema::table(
            'budgets',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'budgets_user_active_dates_index'
                );
            }
        );
    }
};
