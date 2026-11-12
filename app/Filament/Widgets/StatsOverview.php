<?php

namespace App\Filament\Widgets;

use App\ContentTypes\Project;
use App\Models\ContactMessage;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Psyao\Kami\Content\Entry;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // One per Project, read in the Admin's Locale.
        $publishedProjects = Entry::query()->ofType(Project::class)->get();
        $draftProjects = Entry::query()->ofType(Project::class)->withUnpublished()->count() - $publishedProjects->count();

        return [
            Stat::make('Unread messages', ContactMessage::query()->whereNull('read_at')->count())
                ->icon(Heroicon::OutlinedInbox),
            Stat::make('Published projects', $publishedProjects->count())
                ->description("$draftProjects draft")
                ->icon(Heroicon::OutlinedBriefcase),
            Stat::make('Featured projects', $publishedProjects->where('featured', true)->count())
                ->icon(Heroicon::OutlinedStar),
        ];
    }
}
