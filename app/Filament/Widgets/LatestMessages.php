<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestMessages extends TableWidget
{
    protected static ?string $heading = 'Messages non lus';

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->whereNull('read_at'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Reçu')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Sujet')
                    ->limit(50)
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->recordActions([
                Action::make('view')
                    ->label('Voir')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (): string => ContactMessageResource::getUrl('index')),
            ])
            ->emptyStateHeading('Aucun message non lu')
            ->emptyStateIcon(Heroicon::OutlinedInbox);
    }
}
