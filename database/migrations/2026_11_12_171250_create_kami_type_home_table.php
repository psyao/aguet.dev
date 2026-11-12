<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kami_type_home', function (Blueprint $table): void {
            $table->foreignUlid('entry_id')->constrained('kami_entries', indexName: 'kami_fk_8675a76b8509e7c217ef461b83ff9f77')->cascadeOnDelete();
            $table->string('locale', 20);
            $table->text('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->string('hero_role')->nullable();
            $table->string('hero_location')->nullable();
            $table->string('hero_exp')->nullable();
            $table->string('hero_focus')->nullable();
            $table->json('about_body')->nullable();
            $table->string('contact_lead')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_linkedin')->nullable();
            $table->string('contact_linkedin_label')->nullable();
            $table->string('contact_github')->nullable();
            $table->string('contact_github_label')->nullable();
            $table->unique(['entry_id', 'locale'], 'kami_idx_5ffe843e99d9eb92bbb92b51dca2ea39');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kami_type_home');
    }
};
