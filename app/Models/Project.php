<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @method static Builder published()
 */
#[Translatable('name', 'client', 'role', 'summary')]
class Project extends Model
{
    use HasTags;
    use HasTranslations;

    protected $fillable = [
        'slug',
        'name',
        'client',
        'role',
        'summary',
        'url',
        'featured',
        'sort_order',
        'is_published',
    ];

    /** Mirror the migration's column defaults for unsaved instances. */
    protected $attributes = [
        'featured' => false,
        'sort_order' => 0,
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
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
