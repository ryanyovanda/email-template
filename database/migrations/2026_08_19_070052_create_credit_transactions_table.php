<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Signed: positive grants and rewards, negative spends. A balance is
            // the sum of these rows, so nothing is ever silently overwritten and
            // every movement stays auditable.
            $table->integer('amount');

            $table->string('reason');
            $table->string('description')->nullable();

            // 'YYYY-MM' for the monthly top-up. Unique per user and reason, which
            // makes granting the same month twice impossible even if two requests
            // race. NULL for everything else, and repeated NULLs stay legal.
            $table->string('period', 7)->nullable();

            // What the movement paid for, or was earned by.
            $table->foreignId('ai_generation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();

            // The admin behind a manual adjustment.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'reason', 'period']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
