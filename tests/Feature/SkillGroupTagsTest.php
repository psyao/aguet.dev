<?php

namespace Tests\Feature;

use App\Models\SkillGroup;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SkillGroupTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_are_ordered_by_pivot_position_then_name(): void
    {
        $group = SkillGroup::create([
            'title' => ['fr' => 'Démo', 'en' => 'Demo'],
            'sort_order' => 99,
        ]);

        $zebra = Tag::create(['name' => 'Zebra']);
        $alpha = Tag::create(['name' => 'Alpha']);

        $group->tags()->attach([
            $zebra->id => ['position' => 0],
            $alpha->id => ['position' => 1],
        ]);

        $this->assertSame(['Zebra', 'Alpha'], $group->tags->pluck('name')->all());
    }

    public function test_project_and_skill_group_tags_do_not_leak_into_each_other(): void
    {
        $group = SkillGroup::create([
            'title' => ['fr' => 'Démo', 'en' => 'Demo'],
            'sort_order' => 99,
        ]);
        $tag = Tag::create(['name' => 'Laravel']);
        $group->tags()->attach($tag->id, ['position' => 0]);

        $this->assertSame(1, $group->tags()->count());
        $this->assertSame(0, $tag->projects()->count());
    }

    public function test_items_column_is_gone(): void
    {
        $this->assertFalse(Schema::hasColumn('skill_groups', 'items'));
    }
}
