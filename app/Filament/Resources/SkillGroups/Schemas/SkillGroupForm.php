<?php

namespace App\Filament\Resources\SkillGroups\Schemas;

use App\Filament\Forms\TagsSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SkillGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenu')
                    ->description('Champs traduisibles (FR / EN).')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->translatable(),
                        TextInput::make('text')
                            ->label('Texte (au lieu des tags)')
                            ->maxLength(255)
                            ->helperText('Si rempli, remplace les tags — ex. le groupe « Langues ».')
                            ->translatable(),
                        TextInput::make('note')
                            ->label('Note')
                            ->maxLength(255)
                            ->helperText('Affichée dans le pied de l’arbre pour le groupe ★.')
                            ->translatable(),
                    ]),

                Section::make('Métadonnées')
                    ->columns(2)
                    ->schema([
                        TagsSelect::make()
                            ->label('Tags')
                            ->helperText('Laisser vide si « Texte » est rempli — ex. le groupe « Langues ».')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Ordre de tri')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('focus')
                            ->label('Groupe ★ (focus)')
                            ->helperText('Mis en avant dans l’arbre et repris dans le pied.'),
                    ]),
            ]);
    }
}
