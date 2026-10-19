<?php

use App\Models\User;
use Database\Seeders\HomeSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Velox\Cms\Filament\Resources\Entries\Pages\EditEntry;
use Velox\Cms\Models\Entry;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HomeSeeder::class);
    $this->actingAs(User::factory()->create());

    // As the Home Content Type's navigation item opens its Entry Resource.
    Filament::setCurrentPanel('admin');
    Filament::setCurrentResourceConfigurationKey('home');
});

it('shows a Home Page text edited in the Admin on the page', function () {
    Livewire::test(EditEntry::class, ['record' => Entry::homePage('fr')->getRouteKey()])
        ->fillForm(['data.hero_role' => 'Artisan Laravel — Lausanne'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('Artisan Laravel — Lausanne');
    $this->get('/en')->assertSee('Full-stack web developer — Lake Geneva region');
});

it('edits the English texts on the Translation', function () {
    Livewire::test(EditEntry::class, ['record' => Entry::homePage('en')->getRouteKey()])
        ->fillForm(['data.contact_lead' => 'Got a *Laravel* project?'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/en')->assertSee('Got a <em>Laravel</em> project?', false);
    $this->get('/')->assertDontSee('Got a', false);
});

it('previews an unsaved Home Page text, leaving the page as saved', function () {
    $page = Livewire::test(EditEntry::class, ['record' => Entry::homePage('fr')->getRouteKey()])
        ->fillForm(['data.hero_location' => 'Montreux · Suisse'])
        ->callAction('preview')
        ->assertHasNoErrors();

    $url = collect($page->effects['xjs'] ?? [])->pluck('params')->flatten()->sole();

    $this->get($url)->assertOk()->assertSee('Montreux · Suisse');
    $this->get('/')->assertDontSee('Montreux · Suisse');
});
