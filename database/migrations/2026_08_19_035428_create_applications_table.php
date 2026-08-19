<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();

            $table->string('title');
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->string('recipient_name')->nullable();
            $table->longText('job_post')->nullable();

            $table->string('mode')->default('manual'); // manual | ai
            $table->json('field_values')->nullable();
            $table->longText('rendered_html')->nullable();

            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('last_copied_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
