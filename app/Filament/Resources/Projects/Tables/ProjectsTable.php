<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('name')
                    ->label('Nom')
                    ->weight('medium'),
                TextColumn::make('client')
                    ->label('Client')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('tags.name')
                    ->label('Stack')
                    ->badge()
                    ->toggleable(),
                IconColumn::make('featured')
                    ->label('Phare')
                    ->boolean(),
                ToggleColumn::make('is_published')
                    ->label('Publié'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
