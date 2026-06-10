<?php

namespace App\Filament\Pages;

use App\Models\SiteContent;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Singleton editor for the editorial content (SiteContent). All fields are
 * translatable (FR / EN) via the outerweb plugin's Tabs.
 */
class ManageSiteContent extends Page
{
    protected string $view = 'filament.pages.manage-site-content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Contenu du site';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Contenu éditorial';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteContent::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hero')
                    ->schema([
                        Textarea::make('hero_title')
                            ->label('Titre')
                            ->rows(2)
                            ->helperText('**mot** = accent vert · un retour à la ligne = saut de ligne.')
                            ->translatable(),
                        Textarea::make('hero_subtitle')
                            ->label('Sous-titre')
                            ->rows(2)
                            ->translatable(),
                        TextInput::make('hero_role')
                            ->label('Rôle (ligne « whoami »)')
                            ->translatable(),
                        TextInput::make('hero_location')
                            ->label('Localisation')
                            ->translatable(),
                        TextInput::make('hero_exp')
                            ->label('Expérience')
                            ->translatable(),
                        TextInput::make('hero_focus')
                            ->label('Focus')
                            ->translatable(),
                    ]),

                Section::make('À propos')
                    ->schema([
                        Textarea::make('about_body')
                            ->label('Texte (Markdown)')
                            ->rows(8)
                            ->helperText('Markdown : laisser une ligne vide entre les paragraphes.')
                            ->translatable(),
                    ]),

                Section::make('Contact')
                    ->schema([
                        Textarea::make('contact_lead')
                            ->label('Accroche')
                            ->rows(2)
                            ->translatable(),
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_linkedin')
                            ->label('LinkedIn (URL)')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('contact_linkedin_label')
                            ->label('LinkedIn (libellé affiché)')
                            ->maxLength(255)
                            ->placeholder('/in/steveaguet'),
                        TextInput::make('contact_github')
                            ->label('GitHub (URL)')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('contact_github_label')
                            ->label('GitHub (libellé affiché)')
                            ->maxLength(255)
                            ->placeholder('/psyao'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        SiteContent::current()->update($this->form->getState());

        Notification::make()
            ->title('Contenu enregistré')
            ->success()
            ->send();
    }
}
