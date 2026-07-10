<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Project;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $publishedProjects = Project::query()->where('is_published', true)->count();
        $draftProjects = Project::query()->where('is_published', false)->count();

        return [
            Stat::make('Unread messages', ContactMessage::query()->whereNull('read_at')->count())
                ->icon(Heroicon::OutlinedInbox),
            Stat::make('Published projects', $publishedProjects)
                ->description("$draftProjects draft")
                ->icon(Heroicon::OutlinedBriefcase),
            Stat::make('Featured projects', Project::query()->where('featured', true)->count())
                ->icon(Heroicon::OutlinedStar),
        ];
    }
}
