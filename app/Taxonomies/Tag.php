<?php

namespace App\Taxonomies;

use Psyao\Kami\Content\Taxonomy;

/**
 * Shared tech tags (« Laravel », « a11y »…) of Projects and SkillGroups: one
 * name in every Locale, never translated, with no address of their own.
 */
final class Tag extends Taxonomy
{
    #[\Override]
    protected bool $translatable = false;

    #[\Override]
    protected bool $routable = false;
}
