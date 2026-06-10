<?php

use Database\Seeders\SkillGroupSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skill groups for the « tree ~/stack » section. Translatable fields are
     * JSON (spatie/laravel-translatable). The seeder call backfills
     * production, where deploy only runs `migrate --force` — safe because
     * the table is brand new. Database\Seeders is in the main composer
     * autoload, so this also works on a --no-dev install.
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

        (new SkillGroupSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_groups');
    }
};
