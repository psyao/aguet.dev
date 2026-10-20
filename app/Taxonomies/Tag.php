<?php

namespace App\Taxonomies;

use Velox\Cms\Taxonomy;

/**
 * Shared tech tags (« Laravel », « a11y »…) of Projects and SkillGroups: one
 * name in every Locale, never translated, with no Archive.
 */
class Tag extends Taxonomy
{
    public function translatable(): bool
    {
        return false;
    }

    public function public(): bool
    {
        return false;
    }
}
