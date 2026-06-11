<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/** Shared ordered tags relation for taggable models. */
trait HasTags
{
    /** Tags in manual display order (pivot position), name as tiebreaker. */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot('position')
            ->orderByPivot('position')
            ->orderBy('name');
    }
}
