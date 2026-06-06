<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Projects collection. name/client/role/summary are translatable JSON
     * (spatie); the rest are shared across locales.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('client')->nullable();
            $table->json('role')->nullable();
            $table->json('summary')->nullable();
            $table->json('stack')->nullable();        // array of tags
            $table->string('url')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'featured', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
