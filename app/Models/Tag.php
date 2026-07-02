<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * A shared, non-translatable tech tag (« Laravel », « a11y », …), attached
 * to taggables through the `taggables` morph pivot, ordered by `position`.
 */
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $guarded = [];

    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable');
    }

    /** Names are trimmed and inner whitespace collapsed, whatever the entry point. */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::squish($value),
        );
    }

    /**
     * Sanitize a raw list of tag names: keep strings only, normalize
     * whitespace, drop blanks, dedupe case-insensitively keeping the
     * first occurrence's casing and position.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function sanitizeNames(array $values): array
    {
        $seen = [];
        $result = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $name = Str::squish($value);

            if ($name === '' || isset($seen[mb_strtolower($name)])) {
                continue;
            }

            $seen[mb_strtolower($name)] = true;
            $result[] = $name;
        }

        return $result;
    }
}
