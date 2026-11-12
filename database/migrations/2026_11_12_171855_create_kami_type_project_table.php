<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kami_type_project', function (Blueprint $table): void {
            $table->foreignUlid('entry_id')->constrained('kami_entries', indexName: 'kami_fk_23368792f06ac78be041471b94e97a3b')->cascadeOnDelete();
            $table->string('locale', 20);
            $table->string('client')->nullable();
            $table->string('role')->nullable();
            $table->text('summary')->nullable();
            $table->string('website')->nullable();
            $table->boolean('featured')->nullable();
            $table->string('directory')->nullable();
            $table->unique(['entry_id', 'locale'], 'kami_idx_216a6751a05d044a293aaa2643b82dbc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kami_type_project');
    }
};
