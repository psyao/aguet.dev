<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The content tables Velox replaced, kept a week after the switch-over so a revert could read them. The migrations
     * that created, filled and moved them went with them: production ran them all, and a fresh database never needs them.
     */
    public function up(): void
    {
        foreach (['taggables', 'tags', 'skill_groups', 'projects', 'site_contents'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // One-way: a backup restores them.
    }
};
