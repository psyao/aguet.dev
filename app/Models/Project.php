<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @method static Builder published()
 */
class Project extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /**
     * Translatable fields (stored as JSON, one key per locale).
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'name',
        'client',
        'role',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'stack' => 'array',
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Tags in manual display order (pivot position), name as tiebreaker. */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot('position')
            ->orderByPivot('position')
            ->orderBy('name');
    }

    /** Published projects, in display order: featured first, then sort_order. */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->orderByDesc('featured')
            ->orderBy('sort_order');
    }

    /** Host shown on the project card, derived from the URL (e.g. "cvci.ch"). */
    public function host(): ?string
    {
        if (! $this->url) {
            return null;
        }

        $host = parse_url($this->url, PHP_URL_HOST) ?: $this->url;

        return preg_replace('/^www\./', '', $host);
    }
}
