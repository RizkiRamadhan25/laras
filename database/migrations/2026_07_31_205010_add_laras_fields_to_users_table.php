<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_photo_path')->nullable()->after('password');
            $table->timestamp('onboarding_completed_at')->nullable()
                ->after('profile_photo_path');
            $table->timestamp('last_login_at')->nullable()
                ->after('onboarding_completed_at');
            $table->boolean('is_active')->default(true)
                ->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_photo_path',
                'onboarding_completed_at',
                'last_login_at',
                'is_active',
            ]);

            $table->dropSoftDeletes();
        });
    }
};
