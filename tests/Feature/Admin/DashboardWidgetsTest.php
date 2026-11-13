<?php

use App\ContentTypes\Project;
use App\Filament\Widgets\StatsOverview;
use App\Models\User;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Psyao\Kami\Content\Actions\SaveEntry;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->administrator()->create());
});

it('renders the dashboard with its widgets, not Filament\'s account and info ones', function () {
    $this->get('/admin')->assertOk();

    // Kami adds its own three after aguet's.
    expect(array_values(Livewire::test(Dashboard::class)->instance()->getWidgets()))
        ->toContain(StatsOverview::class)
        ->not->toContain(AccountWidget::class, FilamentInfoWidget::class);
});

it('shows the project counts on the stats widget', function () {
    $save = resolve(SaveEntry::class);
    $published = $save->handle(Project::class, ['title' => 'Publié', 'status' => 'published'], locale: 'fr');
    $save->handle(Project::class, ['title' => 'Published', 'status' => 'published'], $published, 'en');
    $draft = $save->handle(Project::class, ['title' => 'Brouillon'], locale: 'fr');
    $save->handle(Project::class, ['title' => 'Draft'], $draft, 'en');

    Livewire::test(StatsOverview::class)
        ->assertSeeInOrder(['Published projects', '1', '1 draft']);
});
