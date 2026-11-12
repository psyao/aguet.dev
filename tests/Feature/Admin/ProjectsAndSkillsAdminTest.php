<?php

use App\ContentTypes\Project;
use App\ContentTypes\SkillGroup;
use App\Models\User;
use Database\Seeders\HomeSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SkillGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psyao\Kami\Content\Actions\SaveEntry;
use Psyao\Kami\Content\Entry;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([HomeSeeder::class, ProjectSeeder::class, SkillGroupSeeder::class]);
    $this->actingAs(User::factory()->administrator()->create());
});

/** The legend of a Project card. */
function projectLegend(string $host): string
{
    return '<span class="slash">~/projects/</span><b>'.$host.'</b>';
}

/** A seeded Entry of the type, by its French title. */
function seeded(string $type, string $title): Entry
{
    return Entry::query()->ofType($type)->inLocale('fr')->get()->firstWhere('title', $title);
}

it('opens aguet\'s Content Types in the Admin, with no raw translation key', function (string $path) {
    $this->get($path)->assertOk()->assertDontSee('filament-panels::')->assertDontSee('kami-types.');
})->with(['/admin/projects', '/admin/projects/create', '/admin/skill-groups', '/admin/tags']);

it('puts Projects in the order given, the featured one still first', function () {
    resolve(SaveEntry::class)->handle(Project::class, ['position' => 0], seeded(Project::class, 'Vaud Rando'));

    foreach (['/', '/en'] as $url) {
        $this->get($url)->assertSeeInOrder(array_map(projectLegend(...), ['cvci', 'vaud-rando', 'animalia']), false);
    }
});

it('puts SkillGroups in the order given', function () {
    resolve(SaveEntry::class)->handle(SkillGroup::class, ['position' => 0], seeded(SkillGroup::class, 'Langues'));

    $this->get('/')->assertSeeInOrder(['Langues', 'Cœur Laravel'], false);
    $this->get('/en')->assertSeeInOrder(['Languages', 'Laravel core'], false);
});

it('shows a Project\'s Tags in the order they are picked', function () {
    $animalia = seeded(Project::class, 'Animalia');
    $tags = ProjectSeeder::tagIds(['Pest', 'Laravel']);

    resolve(SaveEntry::class)->handle(Project::class, ['tags' => $tags], $animalia);

    foreach (['/', '/en'] as $url) {
        $this->get($url)->assertSee('<span>Pest</span><span>Laravel</span>', false);
    }
});
