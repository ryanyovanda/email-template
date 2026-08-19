<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
            $table->timestamp('banned_at')->nullable()->after('role');
            $table->text('ban_reason')->nullable()->after('banned_at');
            $table->unsignedInteger('ai_monthly_limit')->nullable()->after('ban_reason');
            $table->timestamp('onboarded_at')->nullable()->after('ai_monthly_limit');

            $table->index('role');
            $table->index('banned_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['banned_at']);
            $table->dropColumn(['role', 'banned_at', 'ban_reason', 'ai_monthly_limit', 'onboarded_at']);
        });
    }
};
