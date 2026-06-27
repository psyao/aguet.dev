<?php

namespace App\Filament\Pages;

use App\Support\DatabaseDumper;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackup extends Page
{
    protected string $view = 'filament.pages.database-backup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Sauvegarde DB';

    protected static string|\UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $title = 'Sauvegarde de la base de données';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Télécharger le dump')
                ->icon(Heroicon::ArrowDownTray)
                ->action(fn (): StreamedResponse => $this->download()),
        ];
    }

    private function download(): StreamedResponse
    {
        $sql = app(DatabaseDumper::class)->dump();
        $name = config('database.connections.'.config('database.default').'.database')
            .'-'.now()->format('Y-m-d-Hi').'.sql';

        return response()->streamDownload(fn () => print($sql), $name, [
            'Content-Type' => 'application/sql',
        ]);
    }
}
