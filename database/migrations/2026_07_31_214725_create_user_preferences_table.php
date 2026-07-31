<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('locale', 10)->default('id');
            $table->char('currency_code', 3)->default('IDR');
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('date_format', 20)->default('d/m/Y');
            $table->string('time_format', 20)->default('H:i');

            // 1 = Senin, 7 = Minggu
            $table->unsignedTinyInteger('week_starts_on')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
