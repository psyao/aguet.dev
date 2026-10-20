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
        // One per Project: Origins only, their Translations left out.
        $publishedProjects = Project::query()->visible()->whereNull('origin_id')->count();
        $draftProjects = Project::query()->whereNull('origin_id')->where('status', 'draft')->count();

        return [
            Stat::make('Unread messages', ContactMessage::query()->whereNull('read_at')->count())
                ->icon(Heroicon::OutlinedInbox),
            Stat::make('Published projects', $publishedProjects)
                ->description("$draftProjects draft")
                ->icon(Heroicon::OutlinedBriefcase),
            Stat::make('Featured projects', Project::query()->whereNull('origin_id')->where('data->featured', true)->count())
                ->icon(Heroicon::OutlinedStar),
        ];
    }
}
