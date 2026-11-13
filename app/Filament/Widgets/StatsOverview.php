<?php

namespace App\Filament\Widgets;

use App\ContentTypes\Project;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Psyao\Kami\Content\Entry;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // One per Project, read in the Admin's Locale.
        $visibleProjects = Entry::query()->ofType(Project::class)->get();
        $draftProjects = Entry::query()->ofType(Project::class)->withUnpublished()->count() - $visibleProjects->count();

        return [
            Stat::make('Published projects', $visibleProjects->count())
                ->description("$draftProjects draft")
                ->icon(Heroicon::OutlinedBriefcase),
            Stat::make('Featured projects', $visibleProjects->where('featured', true)->count())
                ->icon(Heroicon::OutlinedStar),
        ];
    }
}
