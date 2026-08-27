<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            // Who may pick this template in the gallery.
            $table->string('visibility')->default('global')->after('is_active');

            // How it came to exist: shipped, hand-written by an admin, or generated
            // for a user by the AI.
            $table->string('origin')->default('system')->after('visibility');

            // The user's own words that produced it, kept so an admin reviewing a
            // template can see what was asked for.
            $table->text('brief')->nullable()->after('origin');

            // Acceptance of the terms that let an admin promote it platform-wide.
            $table->timestamp('terms_accepted_at')->nullable()->after('brief');

            $table->timestamp('promoted_at')->nullable()->after('terms_accepted_at');
            $table->foreignId('promoted_by')->nullable()->after('promoted_at')
                ->constrained('users')->nullOnDelete();

            $table->index(['visibility', 'is_active']);
            $table->index(['created_by', 'origin']);
        });

        // Everything that exists before this migration was authored by an admin.
        DB::table('email_templates')->update([
            'visibility' => 'global',
            'origin' => 'system',
        ]);
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign(['promoted_by']);
            $table->dropIndex(['visibility', 'is_active']);
            $table->dropIndex(['created_by', 'origin']);
            $table->dropColumn([
                'visibility', 'origin', 'brief', 'terms_accepted_at', 'promoted_at', 'promoted_by',
            ]);
        });
    }
};
