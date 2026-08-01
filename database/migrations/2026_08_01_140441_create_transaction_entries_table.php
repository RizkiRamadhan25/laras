<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'transaction_entries',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('transaction_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * Rekening dengan histori ledger tidak boleh
                 * dihapus permanen secara sembarangan.
                 */
                $table->foreignId('account_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->foreignId('finance_category_id')
                    ->nullable()
                    ->constrained('finance_categories')
                    ->nullOnDelete();

                /*
                 * Positif  = saldo rekening bertambah.
                 * Negatif  = saldo rekening berkurang.
                 */
                $table->decimal('amount', 18, 2);

                $table->string('role', 20)
                    ->default('principal');

                $table->string('memo', 160)
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'account_id',
                        'transaction_id',
                    ],
                    'entries_account_transaction_index'
                );

                $table->index(
                    [
                        'finance_category_id',
                        'transaction_id',
                    ],
                    'entries_category_transaction_index'
                );

                $table->index(
                    [
                        'transaction_id',
                        'role',
                    ],
                    'entries_transaction_role_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
    }
};
