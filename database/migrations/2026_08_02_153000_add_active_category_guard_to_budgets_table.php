<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicate = DB::table('budgets')
            ->select([
                'user_id',
                'finance_category_id',
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy([
                'user_id',
                'finance_category_id',
            ])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicate) {
            throw new RuntimeException(
                'Terdapat lebih dari satu anggaran aktif pada kategori yang sama. Rapikan data sebelum menjalankan migration.'
            );
        }

        Schema::table(
            'budgets',
            function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger(
                        'active_finance_category_id'
                    )
                    ->nullable()
                    ->after(
                        'finance_category_id'
                    );
            }
        );

        DB::table('budgets')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->update([
                'active_finance_category_id' => DB::raw(
                    'finance_category_id'
                ),
            ]);

        Schema::table(
            'budgets',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'user_id',
                        'active_finance_category_id',
                    ],
                    'budgets_user_active_category_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'budgets',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'budgets_user_active_category_unique'
                );

                $table->dropColumn(
                    'active_finance_category_id'
                );
            }
        );
    }
};
