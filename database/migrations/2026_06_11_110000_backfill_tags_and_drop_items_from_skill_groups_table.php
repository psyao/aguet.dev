<?php

use App\Models\SkillGroup;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill tags/taggables from the legacy `skill_groups.items` JSON, then
     * drop the column — same pattern as the projects stack migration. Under
     * RefreshDatabase the table is empty and this is a no-op.
     */
    public function up(): void
    {
        foreach (DB::table('skill_groups')->get(['id', 'items']) as $row) {
            $names = Tag::sanitizeNames(json_decode($row->items ?? '[]', true) ?: []);

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
                        'taggable_type' => SkillGroup::class,
                        'taggable_id' => $row->id,
                    ],
                    ['position' => $position],
                );
            }
        }

        Schema::table('skill_groups', function (Blueprint $table) {
            $table->dropColumn('items');
        });
    }

    /** Rebuild `items` from the pivots, in position order. */
    public function down(): void
    {
        Schema::table('skill_groups', function (Blueprint $table) {
            $table->json('items')->nullable();
        });

        $rows = DB::table('taggables')
            ->join('tags', 'tags.id', '=', 'taggables.tag_id')
            ->where('taggable_type', SkillGroup::class)
            ->orderBy('taggables.position')
            ->orderBy('tags.name')
            ->get(['taggables.taggable_id', 'tags.name']);

        foreach ($rows->groupBy('taggable_id') as $groupId => $tagRows) {
            DB::table('skill_groups')->where('id', $groupId)->update([
                'items' => json_encode($tagRows->pluck('name')->values()),
            ]);
        }
    }
};
