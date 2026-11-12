<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kami_type_skill_group', function (Blueprint $table): void {
            $table->foreignUlid('entry_id')->constrained('kami_entries', indexName: 'kami_fk_d470140e18f81d7f2beccf6f0bb89977')->cascadeOnDelete();
            $table->string('locale', 20);
            $table->string('text')->nullable();
            $table->string('note')->nullable();
            $table->boolean('focus')->nullable();
            $table->unique(['entry_id', 'locale'], 'kami_idx_a8b2bbe3535e05a16be610bb8ab4fad3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kami_type_skill_group');
    }
};
