<?php

namespace App\Filament\Pages;

use App\Support\DatabaseDumper;
use App\Support\DatabaseRestorer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
                ->action(fn (DatabaseDumper $dumper): StreamedResponse => $this->download($dumper)),

            Action::make('restore')
                ->label('Restaurer un dump')
                ->icon(Heroicon::ArrowUpTray)
                ->color('danger')
                ->schema([
                    FileUpload::make('dump')
                        ->label('Fichier .sql')
                        // No MIME filter: .sql has no reliable MIME type (browsers
                        // report it empty or application/x-sql), so FilePond would
                        // grey out the file. Admin-only + confirmation is the guard.
                        ->maxSize(51200) // 50 MB ceiling — a dump larger than this is not a real backup.
                        ->storeFiles(false)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Restaurer la base de données')
                ->modalDescription('Écrase TOUTE la base actuelle. Ton compte admin est préservé. Action irréversible.')
                ->modalSubmitActionLabel('Écraser et restaurer')
                ->action(fn (DatabaseRestorer $restorer, array $data) => $this->restore($restorer, $data)),
        ];
    }

    private function download(DatabaseDumper $dumper): StreamedResponse
    {
        $sql = $dumper->dump();
        $name = config('database.connections.'.config('database.default').'.database')
            .'-'.now()->format('Y-m-d-Hi').'.sql';

        return response()->streamDownload(fn () => print ($sql), $name, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /** @param array{dump: mixed} $data */
    private function restore(DatabaseRestorer $restorer, array $data): void
    {
        $upload = is_array($data['dump']) ? reset($data['dump']) : $data['dump'];

        try {
            $restorer->restore($upload->get(), auth()->user());
        } catch (Throwable $e) {
            Notification::make()
                ->title('Échec de la restauration')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Base restaurée')
            ->success()
            ->send();
    }
}
