<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('full_name');
            $table->string('headline')->nullable();
            $table->string('contact_email');
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();

            // Cloudinary photo
            $table->string('photo_url')->nullable();
            $table->string('photo_public_id')->nullable();

            // Cloudinary CV
            $table->string('cv_url')->nullable();
            $table->string('cv_public_id')->nullable();
            $table->string('cv_filename')->nullable();
            $table->string('cv_resource_type')->nullable();
            $table->unsignedBigInteger('cv_bytes')->nullable();

            // Text extracted from the CV, editable by the user, fed to the AI.
            $table->longText('cv_text')->nullable();
            $table->timestamp('cv_parsed_at')->nullable();
            $table->string('cv_parse_status')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
