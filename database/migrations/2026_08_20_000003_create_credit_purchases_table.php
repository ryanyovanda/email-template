<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One credit purchase, from the moment an invoice is created through to
        // payment. Credits are only added to the ledger when a purchase reaches
        // 'paid', and the credit_transaction_id guarantees that happens once.
        Schema::create('credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Snapshot of what was bought, so later edits to a package or its
            // price never change the record of a completed purchase.
            $table->foreignId('credit_package_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('credits');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('IDR');

            // pending -> paid | expired | failed
            $table->string('status')->default('pending');

            // Xendit's identifiers. external_id is the reference we generate and
            // send; it is unique so a duplicate webhook cannot create a second row.
            $table->string('external_id')->unique();
            $table->string('xendit_invoice_id')->nullable();
            $table->string('invoice_url')->nullable();

            // Set once, when the paid webhook first credits the ledger. Its
            // presence is what makes crediting idempotent.
            $table->foreignId('credit_transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_purchases');
    }
};
