<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user AI allowances are now ledger adjustments rather than a column, so
     * an admin's change to someone's balance carries a reason and an author.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_monthly_limit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('ai_monthly_limit')->nullable()->after('ban_reason');
        });
    }
};
