<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_billings',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('subscription_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * Diisi ketika tagihan berhasil dibuat sebagai
                 * transaksi pengeluaran.
                 */
                $table->foreignId('transaction_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->date('scheduled_for');

                $table->decimal('amount', 18, 2);

                $table->string('currency_code', 3)
                    ->default('IDR');

                $table->string('status', 20)
                    ->default('scheduled');

                $table->timestamp('attempted_at')
                    ->nullable();

                $table->timestamp('processed_at')
                    ->nullable();

                $table->text('failure_reason')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                /*
                 * Mencegah satu langganan ditagihkan dua kali
                 * untuk tanggal tagihan yang sama.
                 */
                $table->unique(
                    [
                        'subscription_id',
                        'scheduled_for',
                    ],
                    'subscription_billings_unique_cycle'
                );

                $table->index(
                    [
                        'user_id',
                        'status',
                        'scheduled_for',
                    ],
                    'billings_user_status_date_index'
                );

                $table->index(
                    [
                        'subscription_id',
                        'status',
                    ],
                    'billings_subscription_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscription_billings'
        );
    }
};
