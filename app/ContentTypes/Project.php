<?php

namespace App\ContentTypes;

use App\Models\Project as ProjectEntry;
use App\Taxonomies\Tag;
use Psyao\Kami\Content\ContentType;
use Psyao\Kami\Content\Fields\Boolean;
use Psyao\Kami\Content\Fields\Terms;
use Psyao\Kami\Content\Fields\Text;
use Psyao\Kami\Content\Fields\Textarea;
use Psyao\Kami\Content\Fields\Url;

/**
 * A card of the « ls -l ~/projects » section, its title the project's name.
 * Never served at a URL of its own: the Home Page lists the visible ones.
 */
final class Project extends ContentType
{
    #[\Override]
    protected bool $routable = false;

    #[\Override]
    protected bool $orderable = true;

    #[\Override]
    protected ?string $model = ProjectEntry::class;

    public function fields(): array
    {
        return [
            Text::make('client'),
            Text::make('role'),
            Textarea::make('summary')->inlineMarkdown(),
            Url::make('website')->shared(),
            Boolean::make('featured')->shared(),
            // The card's « ~/projects/<directory> » legend, one for every Locale: Velox's slug, as the type has none.
            Text::make('directory')->shared(),
            Terms::make('tags')->of(Tag::class),
        ];
    }
}
