<?php

namespace App\ContentTypes;

use App\Models\Project as ProjectEntry;
use App\Taxonomies\Tag;
use Velox\Cms\ContentType;
use Velox\Cms\Fields\Boolean;
use Velox\Cms\Fields\Text;
use Velox\Cms\Fields\Textarea;

/**
 * A card of the « ls -l ~/projects » section, its title the project's name.
 * Never served at a URL of its own: the Home Page lists the visible ones.
 */
class Project extends ContentType
{
    public function singularLabel(): string
    {
        return 'Projet';
    }

    public function pluralLabel(): string
    {
        return 'Projets';
    }

    public function icon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public function public(): bool
    {
        return false;
    }

    public function orderable(): bool
    {
        return true;
    }

    public function model(): string
    {
        return ProjectEntry::class;
    }

    public function taxonomies(): array
    {
        return [Tag::class];
    }

    public function fields(): array
    {
        return [
            Text::make('client')->label('Client'),
            Text::make('role')->label('Rôle'),
            Textarea::make('summary')->label('Résumé')->helpText(Home::INLINE_MARKDOWN),
            Text::make('url')->label('URL du site')->translatable(false),
            Boolean::make('featured')->label('Projet phare')
                ->helpText('Affiché en premier, en pleine largeur.')->translatable(false),
        ];
    }
}
