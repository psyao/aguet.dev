<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Through the query builder: the SiteContent model this once used is gone,
     * its content now the Home Page's.
     */
    public function up(): void
    {
        foreach (DB::table('site_contents')->get(['id', 'hero_title']) as $row) {
            $translations = array_map(
                fn (?string $value) => str_replace('**', '*', (string) $value),
                json_decode($row->hero_title ?? '[]', true) ?: [],
            );

            DB::table('site_contents')->where('id', $row->id)
                ->update(['hero_title' => json_encode($translations, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        // One-way data normalization; nothing to revert.
    }
};
