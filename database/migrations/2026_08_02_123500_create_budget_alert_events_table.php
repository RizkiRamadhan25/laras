<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'budget_alert_events',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'budget_period_id'
                    )
                    ->constrained(
                        'budget_periods'
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'alert_level',
                    20
                );

                $table->timestamp(
                    'notified_at'
                );

                $table->timestamps();

                $table->unique(
                    [
                        'budget_period_id',
                        'alert_level',
                    ],
                    'budget_alert_period_level_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'budget_alert_events'
        );
    }
};
