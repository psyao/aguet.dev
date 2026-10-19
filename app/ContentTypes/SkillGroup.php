<?php

namespace App\ContentTypes;

use App\Taxonomies\Tag;
use Velox\Cms\ContentType;
use Velox\Cms\Fields\Boolean;
use Velox\Cms\Fields\Text;

/**
 * A group of the « tree ~/stack » skills section. A group with `text` shows
 * that sentence instead of its Tags; the focus group's `note` feeds the tree's
 * footer. Never served at a URL of its own.
 */
class SkillGroup extends ContentType
{
    public function singularLabel(): string
    {
        return 'Groupe de compétences';
    }

    public function pluralLabel(): string
    {
        return 'Compétences';
    }

    public function icon(): string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public function public(): bool
    {
        return false;
    }

    public function orderable(): bool
    {
        return true;
    }

    public function taxonomies(): array
    {
        return [Tag::class];
    }

    public function fields(): array
    {
        return [
            Text::make('text')->label('Texte (au lieu des tags)')
                ->helpText('Si rempli, remplace les tags — ex. le groupe « Langues ».'),
            Text::make('note')->label('Note')->helpText('Affichée dans le pied de l’arbre pour le groupe ★.'),
            Boolean::make('focus')->label('Groupe ★ (focus)')
                ->helpText('Mis en avant dans l’arbre et repris dans le pied.')->translatable(false),
        ];
    }
}
