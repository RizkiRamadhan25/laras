<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'transactions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('type', 20);
                $table->string('status', 20)
                    ->default('draft');

                $table->string('source', 30)
                    ->default('manual');

                /*
                 * Waktu transaksi disimpan secara konsisten oleh
                 * service transaksi. Konversi zona waktu dilakukan
                 * pada input dan tampilan.
                 */
                $table->dateTime('occurred_at');

                $table->string('description', 160)
                    ->nullable();

                $table->string('counterparty', 120)
                    ->nullable();

                $table->string('reference_number', 100)
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamp('posted_at')
                    ->nullable();

                $table->timestamp('cancelled_at')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    [
                        'user_id',
                        'status',
                        'occurred_at',
                    ],
                    'transactions_user_status_date_index'
                );

                $table->index(
                    [
                        'user_id',
                        'type',
                        'occurred_at',
                    ],
                    'transactions_user_type_date_index'
                );

                $table->index(
                    [
                        'user_id',
                        'source',
                    ],
                    'transactions_user_source_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
