<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Models\Tag;
use App\Rules\UniqueTagName;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(50)
                    ->rule(fn (?Tag $record) => new UniqueTagName($record?->getKey())),
            ]);
    }
}
