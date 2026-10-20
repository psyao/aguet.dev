<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The content move first ran in production dating every Entry and Term as of the deploy. This puts back each one's
     * dates from its former row, still in the old tables: created and published when the row was created, last edited
     * when it was, unless edited in Velox since. Through the query builder, the type handles and Taxonomy as strings,
     * so no rename breaks it. A database the move dated right, or a fresh one, ends up the same.
     */
    public function up(): void
    {
        foreach (DB::table('tags')->get() as $tag) {
            $this->date(DB::table('velox_terms')->where('taxonomy', 'tag')->whereNull('origin_id')->where('name', $tag->name)->value('id'), 'velox_terms', $tag);
        }

        foreach (DB::table('projects')->get() as $row) {
            $this->date($this->origin('project', 'slug', $row->slug), 'velox_entries', $row);
        }

        foreach (DB::table('skill_groups')->get() as $row) {
            $this->date($this->origin('skill-group', 'title', json_decode($row->title, true)['fr'] ?? null), 'velox_entries', $row);
        }

        if (($row = DB::table('site_contents')->orderBy('id')->first()) !== null) {
            $this->date($this->origin('home', 'type', 'home'), 'velox_entries', $row);
        }
    }

    public function down(): void
    {
        // One-way: the move's own dates aren't worth keeping.
    }

    private function origin(string $type, string $column, ?string $value): ?int
    {
        return DB::table('velox_entries')->where('type', $type)->whereNull('origin_id')->where($column, $value)->value('id');
    }

    /** The Entry or Term and, for an Entry, its Translations. */
    private function date(?int $id, string $table, object $former): void
    {
        if ($id === null || $former->created_at === null) {
            return;
        }

        $rows = fn (): Builder => DB::table($table)->where(fn (Builder $query) => $table === 'velox_entries'
            ? $query->where('id', $id)->orWhere('origin_id', $id)
            : $query->where('id', $id));

        // First, while an unedited row's two dates are still the move's.
        $rows()->whereColumn('updated_at', 'created_at')->update(['updated_at' => $former->updated_at ?? $former->created_at]);

        if ($table === 'velox_entries') {
            $rows()->whereNotNull('published_at')->update(['published_at' => $former->created_at]);
        }

        $rows()->update(['created_at' => $former->created_at]);
    }
};
