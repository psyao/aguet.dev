<?php

namespace App\Filament\Forms;

use App\Rules\UniqueTagName;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * Ordered multi-select on a HasTags `tags()` relation: inline tag creation,
 * and selection order persisted as the pivot `position`.
 */
class TagsSelect
{
    public static function make(string $name = 'tags'): Select
    {
        return Select::make($name)
            ->relationship('tags', 'name')
            ->multiple()
            ->searchable()
            ->preload()
            ->createOptionForm([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(50)
                    ->rule(new UniqueTagName),
            ])
            ->saveRelationshipsUsing(function (Select $component, ?array $state): void {
                $component->getRecord()->tags()->sync(
                    collect($state ?? [])
                        ->values()
                        ->mapWithKeys(fn ($tagId, int $index) => [(int) $tagId => ['position' => $index]])
                        ->all(),
                );
            });
    }
}
