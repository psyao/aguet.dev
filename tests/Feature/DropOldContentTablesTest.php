<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('drops the old tables production still has', function () {
    foreach (['site_contents', 'projects', 'skill_groups', 'tags'] as $table) {
        Schema::create($table, fn (Blueprint $blueprint) => $blueprint->id());
    }

    // As in production: a pivot row referencing a Tag, so a drop out of order fails.
    Schema::create('taggables', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tag_id')->constrained();
    });
    DB::table('taggables')->insert(['tag_id' => DB::table('tags')->insertGetId([])]);

    (require database_path('migrations/2026_10_24_000000_drop_the_old_content_tables.php'))->up();

    foreach (['site_contents', 'projects', 'skill_groups', 'tags', 'taggables'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});
