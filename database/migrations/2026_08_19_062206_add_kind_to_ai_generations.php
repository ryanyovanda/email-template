<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            // Application copy and whole templates draw on separate allowances,
            // so the log has to say which one a call was for. Keeping the counter
            // here rather than on users means no schema churn per quota change.
            $table->string('kind')->default('application')->after('application_id');

            $table->foreignId('email_template_id')->nullable()->after('kind')
                ->constrained('email_templates')->nullOnDelete();

            $table->index(['user_id', 'kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropIndex(['user_id', 'kind', 'status']);
            $table->dropColumn(['kind', 'email_template_id']);
        });
    }
};
