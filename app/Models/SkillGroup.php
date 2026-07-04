<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * A group in the « tree ~/stack » skills section. A group with `text` renders
 * that sentence instead of its tags; the focus group's `note` feeds the
 * tree footer.
 *
 * @method static Builder ordered()
 */
#[Translatable('title', 'text', 'note')]
class SkillGroup extends Model
{
    use HasTags;
    use HasTranslations;

    protected $fillable = [
        'title',
        'text',
        'note',
        'focus',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'focus' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Groups in display order. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
