<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_project_seeder_is_idempotent_for_tags(): void
    {
        $this->seed(ProjectSeeder::class);
        $this->seed(ProjectSeeder::class);

        // 15 distinct tags across the 7 seeded projects.
        $this->assertSame(15, Tag::count());

        $cvci = Project::where('slug', 'cvci')->firstOrFail();
        $this->assertSame(
            ['Laravel', 'MS Entra', 'MS Graph', 'Livewire', 'Blade', 'Filament', 'Tailwind CSS'],
            $cvci->tags->pluck('name')->all(),
        );

        $terreEtNature = Project::where('slug', 'terre-et-nature')->firstOrFail();
        $this->assertSame(
            ['WordPress', 'XML', 'Digitization', 'Blade'],
            $terreEtNature->tags->pluck('name')->all(),
        );
    }

    public function test_stack_column_is_gone_and_tag_tables_exist(): void
    {
        $this->assertFalse(Schema::hasColumn('projects', 'stack'));
        $this->assertTrue(Schema::hasTable('tags'));
        $this->assertTrue(Schema::hasTable('taggables'));
    }
}
