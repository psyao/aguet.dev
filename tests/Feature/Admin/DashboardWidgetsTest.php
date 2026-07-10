<?php

use App\Filament\Widgets\LatestMessages;
use App\Filament\Widgets\StatsOverview;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the dashboard with its widgets', function () {
    $this->get('/admin')->assertOk();
});

it('shows unread message and project counts on the stats widget', function () {
    ContactMessage::factory()->count(2)->create(['read_at' => null]);
    ContactMessage::factory()->create(['read_at' => now()]);
    Project::create(['slug' => 'published', 'name' => ['fr' => 'Publié', 'en' => 'Published'], 'is_published' => true]);
    Project::create(['slug' => 'draft', 'name' => ['fr' => 'Brouillon', 'en' => 'Draft'], 'is_published' => false]);

    Livewire::test(StatsOverview::class)
        ->assertSee('2')
        ->assertSee('1 draft');
});

it('lists only unread messages on the latest messages widget', function () {
    ContactMessage::factory()->create(['read_at' => null, 'subject' => 'Unread subject']);
    ContactMessage::factory()->create(['read_at' => now(), 'subject' => 'Read subject']);

    Livewire::test(LatestMessages::class)
        ->assertSee('Unread subject')
        ->assertDontSee('Read subject');
});
