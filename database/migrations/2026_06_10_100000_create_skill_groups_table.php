<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skill groups for the « tree ~/stack » section. Translatable fields are
     * JSON (spatie/laravel-translatable). It once ran SkillGroupSeeder to
     * backfill production, long done; that seeder now writes Velox
     * Entries, whose tables don't exist yet here.
     */
    public function up(): void
    {
        Schema::create('skill_groups', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('items')->nullable();
            $table->json('text')->nullable();
            $table->json('note')->nullable();
            $table->boolean('focus')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_groups');
    }
};
