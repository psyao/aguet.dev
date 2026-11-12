<?php

use App\ContentTypes\Home;
use App\Models\User;
use Database\Seeders\HomeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psyao\Kami\Content\Actions\SaveEntry;
use Psyao\Kami\Content\Entry;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HomeSeeder::class);
    $this->actingAs(User::factory()->administrator()->create());
});

it('opens the Home Page in the Admin in each language', function () {
    $this->get('/admin/home')->assertOk()->assertSee('Rôle (ligne « whoami »)');
    $this->get('/admin/home/en')->assertOk();
});

it('shows a Home Page text saved in French on the French page only', function () {
    resolve(SaveEntry::class)->handle(Home::class, ['hero_role' => 'Artisan Laravel — Lausanne'], Entry::query()->ofType(Home::class)->sole(), 'fr');

    $this->get('/')->assertSee('Artisan Laravel — Lausanne');
    $this->get('/en')->assertSee('Full-stack web developer — Lake Geneva region');
});

it('renders an English text\'s inline Markdown on the English page only', function () {
    resolve(SaveEntry::class)->handle(Home::class, ['contact_lead' => 'Got a *Laravel* project?'], Entry::query()->ofType(Home::class)->sole(), 'en');

    $this->get('/en')->assertSee('Got a <em>Laravel</em> project?', false);
    $this->get('/')->assertDontSee('Got a', false);
});
