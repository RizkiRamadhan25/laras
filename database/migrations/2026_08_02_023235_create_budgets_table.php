<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'budgets',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'finance_category_id'
                    )
                    ->constrained(
                        'finance_categories'
                    )
                    ->restrictOnDelete();

                $table->string(
                    'name',
                    120
                );

                $table->decimal(
                    'amount',
                    18,
                    2
                );

                $table
                    ->string(
                        'period_type',
                        30
                    )
                    ->default('monthly');

                $table
                    ->decimal(
                        'warning_threshold_percent',
                        5,
                        2
                    )
                    ->default(80);

                $table->date('start_date');

                $table
                    ->date('end_date')
                    ->nullable();

                $table
                    ->boolean('is_recurring')
                    ->default(true);

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table->timestamps();
                $table->softDeletes();

                $table->index([
                    'user_id',
                    'is_active',
                ]);

                $table->index([
                    'user_id',
                    'finance_category_id',
                    'is_active',
                ]);

                $table->index([
                    'period_type',
                    'start_date',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
