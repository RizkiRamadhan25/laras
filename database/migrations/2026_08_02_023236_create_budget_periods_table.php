<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'budget_periods',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('budget_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->date('period_start');
                $table->date('period_end');

                $table->decimal(
                    'budget_amount',
                    18,
                    2
                );

                $table
                    ->decimal(
                        'used_amount',
                        18,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'remaining_amount',
                        18,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'usage_percent',
                        7,
                        2
                    )
                    ->default(0);

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default('upcoming');

                $table->timestamps();

                $table->unique(
                    [
                        'budget_id',
                        'period_start',
                        'period_end',
                    ],
                    'budget_period_unique'
                );

                $table->index([
                    'status',
                    'period_start',
                    'period_end',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'budget_periods'
        );
    }
};
