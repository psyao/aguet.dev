<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists Kami\'s received messages and aguet\'s Content Types in French', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $this->get('/admin')->assertOk();

    $labels = collect(Filament::getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all())
        ->map->getLabel()
        ->all();

    expect($labels)->toContain('Messages reçus', 'Page d’accueil', 'Projets', 'Compétences', 'Tags');
});
