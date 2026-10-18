<?php

namespace App\Taxonomies;

use Velox\Cms\Taxonomy;

class Tag extends Taxonomy
{
    public function prefix(): string
    {
        return 'tag';
    }

    public function hierarchical(): bool
    {
        return false;
    }

    /** At most one Term per Entry. */
    public function single(): bool
    {
        return false;
    }

    /** At least one Term per Entry. */
    public function required(): bool
    {
        return false;
    }

    /** Whether each Term has an Archive page. */
    public function public(): bool
    {
        return true;
    }
}
