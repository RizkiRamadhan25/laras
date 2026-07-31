<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('type', 30);
            $table->string('institution', 100)->nullable();

            $table->char('currency_code', 3)
                ->default('IDR');

            $table->decimal('initial_balance', 18, 2)
                ->default(0);

            $table->decimal('cached_balance', 18, 2)
                ->default(0);

            $table->char('account_number_last_four', 4)
                ->nullable();

            $table->char('color', 7)
                ->nullable();

            $table->string('icon', 50)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'user_id',
                'is_active',
            ]);

            $table->index([
                'user_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
