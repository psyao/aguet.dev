<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subject')
                            ->label('Sujet')
                            ->columnSpanFull(),
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Reçu le')
                            ->dateTime('d.m.Y H:i'),
                        // TextEntry escapes its value — the visitor-supplied
                        // message renders as plain text, never HTML.
                        TextEntry::make('message')
                            ->label('Message')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Section::make('Livraison')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('notified_at')
                            ->label('Notifié le')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('— en attente —'),
                        TextEntry::make('notify_attempts')
                            ->label('Tentatives')
                            ->badge(),
                        TextEntry::make('read_at')
                            ->label('Lu le')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('— non lu —'),
                    ]),
            ]);
    }
}
