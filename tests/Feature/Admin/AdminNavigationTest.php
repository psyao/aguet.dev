<?php

use App\Filament\Pages\DatabaseBackup;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the inbox first, then Velox\'s Content and Settings groups, the backup under Settings', function () {
    $this->actingAs(User::factory()->create());
    $this->get('/admin')->assertOk();

    $navigation = collect(Filament::getNavigation());
    $ungrouped = $navigation->first(fn (NavigationGroup $group): bool => blank($group->getLabel()));

    expect($navigation->map(fn (NavigationGroup $group): ?string => $group->getLabel())->filter()->values()->all())
        ->toBe(['Contenu', 'Paramètres'])
        ->and(collect($ungrouped->getItems())->map->getLabel()->all())
        ->toBe(['Tableau de bord', ContactMessageResource::getNavigationLabel()])
        ->and(collect($navigation->last()->getItems())->map->getLabel()->all())
        ->toContain(DatabaseBackup::getNavigationLabel());
});
