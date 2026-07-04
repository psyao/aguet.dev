<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFormTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_selection_order_is_saved_as_pivot_positions(): void
    {
        $project = Project::create([
            'slug' => 'demo',
            'name' => ['fr' => 'Démo', 'en' => 'Demo'],
        ]);
        $php = Tag::create(['name' => 'PHP']);
        $vue = Tag::create(['name' => 'Vue']);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['tags' => [$vue->id, $php->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Vue', 'PHP'], $project->refresh()->tags->pluck('name')->all());
        $this->assertSame([0, 1], $project->tags->pluck('pivot.position')->all());
    }

    public function test_create_persists_every_field_including_both_locales(): void
    {
        $php = Tag::create(['name' => 'PHP']);

        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => ['fr' => 'Nom FR', 'en' => 'Name EN'],
                'client' => ['fr' => 'Client FR', 'en' => 'Client EN'],
                'role' => ['fr' => 'Rôle FR', 'en' => 'Role EN'],
                'summary' => ['fr' => 'Résumé FR', 'en' => 'Summary EN'],
                'slug' => 'mon-projet',
                'url' => 'https://example.ch',
                'tags' => [$php->id],
                'sort_order' => 7,
                'featured' => true,
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::firstWhere('slug', 'mon-projet');

        $this->assertNotNull($project);
        $this->assertSame('Nom FR', $project->getTranslation('name', 'fr'));
        $this->assertSame('Name EN', $project->getTranslation('name', 'en'));
        $this->assertSame('Client EN', $project->getTranslation('client', 'en'));
        $this->assertSame('Rôle FR', $project->getTranslation('role', 'fr'));
        $this->assertStringContainsString('Summary EN', $project->getTranslation('summary', 'en'));
        $this->assertSame('https://example.ch', $project->url);
        $this->assertSame(7, $project->sort_order);
        $this->assertTrue($project->featured);
        $this->assertFalse($project->is_published);
        $this->assertSame(['PHP'], $project->tags->pluck('name')->all());
    }

    public function test_resaving_with_fewer_tags_detaches_the_removed_ones(): void
    {
        $project = Project::create([
            'slug' => 'demo',
            'name' => ['fr' => 'Démo', 'en' => 'Demo'],
        ]);
        $php = Tag::create(['name' => 'PHP']);
        $vue = Tag::create(['name' => 'Vue']);
        $project->tags()->attach([
            $php->id => ['position' => 0],
            $vue->id => ['position' => 1],
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['tags' => [$vue->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Vue'], $project->refresh()->tags->pluck('name')->all());
    }
}
