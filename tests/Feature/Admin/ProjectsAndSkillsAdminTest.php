<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\HomeSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SkillGroupSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Velox\Cms\Filament\Resources\Entries\Pages\CreateEntry;
use Velox\Cms\Filament\Resources\Entries\Pages\EditEntry;
use Velox\Cms\Filament\Resources\Entries\Pages\ListEntries;
use Velox\Cms\Filament\Resources\Terms\Pages\ListTerms;
use Velox\Cms\Models\Entry;
use Velox\Cms\Models\Term;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([HomeSeeder::class, ProjectSeeder::class, SkillGroupSeeder::class]);
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

/** The legend of a Project card. */
function projectLegend(string $slug): string
{
    return '<span class="slash">~/projects/</span><b>'.$slug.'</b>';
}

/** The French Origin of a seeded Project. */
function seededProject(string $slug): Project
{
    return Project::query()->inLocale('fr')->where('slug', $slug)->sole();
}

it('puts Projects in the order they are dragged into, the featured one still first', function () {
    Filament::setCurrentResourceConfigurationKey('project');

    $slugs = ['vaud-rando', 'terre-et-nature', 'montreux-noel-2023', 'cvci-125', 'atr-booking', 'animalia', 'cvci'];

    Livewire::test(ListEntries::class)->call('reorderTable', array_map(fn (string $slug) => seededProject($slug)->getKey(), $slugs));

    foreach (['/', '/en'] as $url) {
        $this->get($url)->assertSeeInOrder(array_map(projectLegend(...), ['cvci', ...array_slice($slugs, 0, 6)]), false);
    }
});

it('puts SkillGroups in the order they are dragged into', function () {
    Filament::setCurrentResourceConfigurationKey('skill-group');

    $groups = Entry::query()->where('type', 'skill-group')->inLocale('fr')->ordered()->get();

    Livewire::test(ListEntries::class)->call('reorderTable', $groups->reverse()->modelKeys());

    $this->get('/')->assertSeeInOrder(['Langues', 'Frontend', 'Bases de données', 'Intégration &amp; automatisation', 'Cœur Laravel'], false);
    $this->get('/en')->assertSeeInOrder(['Languages', 'Front-end', 'Databases', 'Integration &amp; automation', 'Laravel core'], false);
});

it('shows a Project\'s Tags in the order they are picked, one created inline', function () {
    Filament::setCurrentResourceConfigurationKey('project');

    $page = Livewire::test(EditEntry::class, ['record' => seededProject('animalia')->getRouteKey()])
        ->callAction(TestAction::make('createOption')->schemaComponent('taxonomies.tag', 'form'), ['name' => 'Pest'])
        ->assertHasNoErrors();

    $pest = Term::query()->where('name', 'Pest')->sole();
    $laravel = Term::query()->where('name', 'Laravel')->sole();

    $page->fillForm(['taxonomies.tag' => [$pest->id, $laravel->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    foreach (['/', '/en'] as $url) {
        $this->get($url)->assertSee('<span>Pest</span><span>Laravel</span>', false);
    }
});

it('offers no Translate action on Tags', function () {
    Filament::setCurrentResourceConfigurationKey('tag');

    $laravel = Term::query()->where('name', 'Laravel')->sole();

    Livewire::test(ListTerms::class)
        ->assertCanSeeTableRecords([$laravel])
        ->assertTableActionHidden('translate', $laravel);
});

it('shows no raw Filament translation key, a string French lacks falling back to English', function () {
    $this->get('/admin/entries/project/create')
        ->assertOk()
        ->assertSee('Skip to content')
        ->assertDontSee('filament-panels::');
});

it('labels a Project\'s title « Nom », as the old Admin did', function () {
    Filament::setCurrentResourceConfigurationKey('project');
    $named = fn (TextInput|TextColumn $title): bool => $title->getLabel() === 'Nom';

    Livewire::test(CreateEntry::class)->assertFormFieldExists('title', $named);
    Livewire::test(ListEntries::class)->assertTableColumnExists('title', $named);
});
