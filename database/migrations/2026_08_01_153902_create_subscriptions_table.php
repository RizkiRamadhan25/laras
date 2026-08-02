<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscriptions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * Rekening dan kategori memakai soft delete.
                 * Data histori masih dapat mempertahankan relasinya.
                 */
                $table->foreignId('account_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->foreignId('finance_category_id')
                    ->constrained('finance_categories')
                    ->restrictOnDelete();

                $table->string('name', 160);

                $table->string('provider', 120)
                    ->nullable();

                $table->decimal('amount', 18, 2);

                $table->char('currency_code', 3)
                    ->default('IDR');

                /*
                 * Contoh:
                 * interval_count = 1, interval_unit = month
                 * berarti setiap bulan.
                 *
                 * interval_count = 3, interval_unit = month
                 * berarti setiap tiga bulan.
                 */
                $table->string('interval_unit', 20);

                $table->unsignedSmallInteger('interval_count')
                    ->default(1);

                $table->date('started_on');

                $table->date('next_billing_on')
                    ->nullable();

                $table->date('end_on')
                    ->nullable();

                /*
                 * Waktu pemrosesan mengikuti timezone pengguna.
                 */
                $table->time('billing_time')
                    ->default('08:00:00');

                /*
                 * Apabila true, scheduler akan membuat transaksi
                 * pengeluaran secara otomatis pada tanggal tagihan.
                 */
                $table->boolean('auto_post')
                    ->default(true);

                /*
                 * Contoh: [3, 1].
                 */
                $table->json('reminder_days')
                    ->nullable();

                $table->string('status', 20)
                    ->default('active');

                $table->date('last_billed_on')
                    ->nullable();

                $table->timestamp('paused_at')
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
                        'next_billing_on',
                    ],
                    'subscriptions_user_status_next_index'
                );

                $table->index(
                    [
                        'account_id',
                        'status',
                    ],
                    'subscriptions_account_status_index'
                );

                $table->index(
                    [
                        'finance_category_id',
                        'status',
                    ],
                    'subscriptions_category_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
