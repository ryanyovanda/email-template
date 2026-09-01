<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credit packs an admin puts on sale. Users buy one to top up their
        // balance beyond the monthly allowance.
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // How many credits this pack grants, and what it costs in the
            // smallest currency unit (IDR has no cents, so this is whole rupiah).
            $table->unsignedInteger('credits');
            $table->unsignedInteger('price');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};
