<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editorial singleton. Translatable fields are stored as JSON
     * (spatie/laravel-translatable), one key per locale.
     */
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->json('hero_title')->nullable();
            $table->json('hero_subtitle')->nullable();
            $table->json('hero_role')->nullable();
            $table->json('hero_location')->nullable();
            $table->json('hero_exp')->nullable();
            $table->json('hero_focus')->nullable();
            $table->json('about_body')->nullable();
            $table->json('contact_lead')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
