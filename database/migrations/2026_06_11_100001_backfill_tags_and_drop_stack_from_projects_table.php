<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Backfill tags/taggables from the legacy `projects.stack` JSON, then
     * drop the column. Production deploy only runs `migrate --force`, so the
     * backfill lives here (same pattern as the skill_groups migration). Under
     * RefreshDatabase the projects table is empty and this is a no-op. Through
     * the query builder: the Tag and Project models it once used are gone.
     */
    public function up(): void
    {
        foreach (DB::table('projects')->get(['id', 'stack']) as $row) {
            // Strings only, whitespace squished, blanks dropped, deduplicated case-insensitively.
            $names = collect(json_decode($row->stack ?? '[]', true) ?: [])
                ->filter(fn (mixed $value) => is_string($value))
                ->map(fn (string $value) => Str::squish($value))
                ->reject(fn (string $name) => $name === '')
                ->unique(fn (string $name) => mb_strtolower($name))
                ->all();

            foreach (array_values($names) as $position => $name) {
                $tagId = DB::table('tags')
                    ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                    ->value('id')
                    ?? DB::table('tags')->insertGetId([
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('taggables')->updateOrInsert(
                    [
                        'tag_id' => $tagId,
                        'taggable_type' => 'App\\Models\\Project',
                        'taggable_id' => $row->id,
                    ],
                    ['position' => $position],
                );
            }
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('stack');
        });
    }

    /** Rebuild `stack` from the pivots, in position order. */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('stack')->nullable();
        });

        $rows = DB::table('taggables')
            ->join('tags', 'tags.id', '=', 'taggables.tag_id')
            ->where('taggable_type', 'App\\Models\\Project')
            ->orderBy('taggables.position')
            ->orderBy('tags.name')
            ->get(['taggables.taggable_id', 'tags.name']);

        foreach ($rows->groupBy('taggable_id') as $projectId => $tagRows) {
            DB::table('projects')->where('id', $projectId)->update([
                'stack' => json_encode($tagRows->pluck('name')->values()),
            ]);
        }
    }
};
