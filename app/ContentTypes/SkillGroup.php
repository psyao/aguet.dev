<?php

namespace App\ContentTypes;

use App\Taxonomies\Tag;
use Psyao\Kami\Content\ContentType;
use Psyao\Kami\Content\Fields\Boolean;
use Psyao\Kami\Content\Fields\Terms;
use Psyao\Kami\Content\Fields\Text;

/**
 * A group of the « tree ~/stack » skills section. A group with `text` shows
 * that sentence instead of its Tags; the focus group's `note` feeds the tree's
 * footer. Never served at a URL of its own.
 */
final class SkillGroup extends ContentType
{
    #[\Override]
    protected bool $routable = false;

    #[\Override]
    protected bool $orderable = true;

    public function fields(): array
    {
        return [
            Text::make('text'),
            Text::make('note'),
            Boolean::make('focus')->shared(),
            Terms::make('tags')->of(Tag::class),
        ];
    }
}
