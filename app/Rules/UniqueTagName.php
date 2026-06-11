<?php

namespace App\Rules;

use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Tag names must be unique ignoring case and surrounding whitespace —
 * the DB unique index only catches exact duplicates.
 */
class UniqueTagName implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $value));

        $exists = Tag::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->when($this->ignoreId !== null, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('Ce tag existe déjà.');
        }
    }
}
