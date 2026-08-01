<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'activities',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('title', 160);

                $table->text('description')
                    ->nullable();

                $table->string('type', 20)
                    ->default('task');

                $table->string('priority', 20)
                    ->default('medium');

                $table->string('status', 30)
                    ->default('planned');

                /*
                 * Acara terjadwal menggunakan starts_at dan ends_at.
                 * Tugas dapat memakai starts_at, due_at, atau keduanya.
                 * Deadline wajib memiliki due_at.
                 */
                $table->dateTime('starts_at')
                    ->nullable();

                $table->dateTime('ends_at')
                    ->nullable();

                $table->dateTime('due_at')
                    ->nullable();

                $table->boolean('all_day')
                    ->default(false);

                $table->unsignedSmallInteger('estimated_minutes')
                    ->nullable();

                $table->boolean('is_flexible')
                    ->default(true);

                $table->string('location', 160)
                    ->nullable();

                $table->char('color', 7)
                    ->nullable();

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamp('completed_at')
                    ->nullable();

                $table->timestamp('cancelled_at')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    [
                        'user_id',
                        'status',
                        'due_at',
                    ],
                    'activities_user_status_due_index'
                );

                $table->index(
                    [
                        'user_id',
                        'type',
                        'starts_at',
                    ],
                    'activities_user_type_start_index'
                );

                $table->index(
                    [
                        'user_id',
                        'priority',
                        'status',
                    ],
                    'activities_user_priority_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
