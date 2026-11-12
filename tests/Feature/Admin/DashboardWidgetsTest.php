<?php

use App\ContentTypes\Project;
use App\Filament\Widgets\LatestMessages;
use App\Filament\Widgets\StatsOverview;
use App\Models\ContactMessage;
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
        ->toContain(LatestMessages::class, StatsOverview::class)
        ->not->toContain(AccountWidget::class, FilamentInfoWidget::class);
});

it('shows unread message and project counts on the stats widget', function () {
    ContactMessage::factory()->count(2)->create(['read_at' => null]);
    ContactMessage::factory()->create(['read_at' => now()]);
    $save = resolve(SaveEntry::class);
    $published = $save->handle(Project::class, ['title' => 'Publié', 'status' => 'published'], locale: 'fr');
    $save->handle(Project::class, ['title' => 'Published', 'status' => 'published'], $published, 'en');
    $draft = $save->handle(Project::class, ['title' => 'Brouillon'], locale: 'fr');
    $save->handle(Project::class, ['title' => 'Draft'], $draft, 'en');

    Livewire::test(StatsOverview::class)
        ->assertSeeInOrder(['Unread messages', '2', 'Published projects', '1', '1 draft']);
});

it('lists only unread messages on the latest messages widget', function () {
    ContactMessage::factory()->create(['read_at' => null, 'subject' => 'Unread subject']);
    ContactMessage::factory()->create(['read_at' => now(), 'subject' => 'Read subject']);

    Livewire::test(LatestMessages::class)
        ->assertSee('Unread subject')
        ->assertDontSee('Read subject');
});
