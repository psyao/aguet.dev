<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use App\Services\ContactMessageNotifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('read_at')
                    ->label('Lu')
                    ->boolean()
                    ->state(fn (ContactMessage $record): bool => $record->read_at !== null),
                TextColumn::make('created_at')
                    ->label('Reçu')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Sujet')
                    ->limit(50)
                    ->weight(fn (ContactMessage $record): string => $record->read_at ? 'normal' : 'bold')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('notified_at')
                    ->label('Notifié')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('— en attente —')
                    ->toggleable(),
                TextColumn::make('notify_attempts')
                    ->label('Tentatives')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= ContactMessageNotifier::MAX_ATTEMPTS => 'danger',
                        $state > 0 => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('markRead')
                    ->label('Marquer comme lu')
                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->visible(fn (ContactMessage $record): bool => $record->read_at === null)
                    ->action(fn (ContactMessage $record) => $record->forceFill(['read_at' => now()])->save()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
