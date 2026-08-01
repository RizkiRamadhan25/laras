<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'recommendation_interactions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'recommendation_key',
                    190
                );

                $table->string(
                    'recommendation_kind',
                    64
                );

                $table->string(
                    'interaction_type',
                    32
                );

                $table->string(
                    'title',
                    200
                );

                $table->json('snapshot')
                    ->nullable();

                $table->timestamp(
                    'occurred_at'
                );

                $table->timestamps();

                $table->index([
                    'user_id',
                    'occurred_at',
                ]);

                $table->index([
                    'user_id',
                    'recommendation_key',
                ]);

                $table->index([
                    'user_id',
                    'interaction_type',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'recommendation_interactions'
        );
    }
};
