<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'finance_categories',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('name', 100);
                $table->string('flow_type', 20);

                $table->string('icon', 50)
                    ->nullable();

                $table->char('color', 7)
                    ->nullable();

                $table->boolean('is_system')
                    ->default(false);

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedSmallInteger('sort_order')
                    ->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    [
                        'user_id',
                        'flow_type',
                        'is_active',
                    ],
                    'categories_user_flow_active_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_categories');
    }
};
