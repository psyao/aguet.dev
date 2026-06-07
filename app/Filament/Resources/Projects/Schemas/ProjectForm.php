<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenu')
                    ->description('Champs traduisibles (FR / EN).')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->translatable(),
                        TextInput::make('client')
                            ->label('Client')
                            ->maxLength(255)
                            ->translatable(),
                        TextInput::make('role')
                            ->label('Rôle')
                            ->maxLength(255)
                            ->translatable(),
                        Textarea::make('summary')
                            ->label('Résumé')
                            ->rows(3)
                            ->maxLength(1000)
                            ->translatable(),
                    ]),

                Section::make('Métadonnées')
                    ->columns(2)
                    ->schema([
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Identifiant unique, partagé entre les langues (ex. « cvci »).')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('url')
                            ->label('URL du site')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://exemple.ch'),
                        TagsInput::make('stack')
                            ->label('Stack (tags)')
                            ->placeholder('Ajouter un tag')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Ordre de tri')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('featured')
                            ->label('Projet phare')
                            ->helperText('Affiché en pleine largeur, en avant.'),
                        Toggle::make('is_published')
                            ->label('Publié')
                            ->default(true),
                    ]),
            ]);
    }
}
