<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_are_ordered_by_pivot_position_then_name(): void
    {
        $project = Project::create([
            'slug' => 'demo',
            'name' => ['fr' => 'Démo', 'en' => 'Demo'],
        ]);

        $zebra = Tag::create(['name' => 'Zebra']);
        $alpha = Tag::create(['name' => 'Alpha']);
        $mango = Tag::create(['name' => 'Mango']);

        // Attach out of alphabetical order; Mango and Alpha share position 1.
        $project->tags()->attach([
            $zebra->id => ['position' => 0],
            $mango->id => ['position' => 1],
            $alpha->id => ['position' => 1],
        ]);

        $this->assertSame(
            ['Zebra', 'Alpha', 'Mango'],
            $project->tags->pluck('name')->all(),
        );
    }

    public function test_deleting_a_tag_detaches_it_from_projects(): void
    {
        $project = Project::create([
            'slug' => 'demo',
            'name' => ['fr' => 'Démo', 'en' => 'Demo'],
        ]);
        $tag = Tag::create(['name' => 'Laravel']);
        $project->tags()->attach($tag->id, ['position' => 0]);

        $tag->delete();

        $this->assertSame(0, $project->tags()->count());
    }
}
