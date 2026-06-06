<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SiteContent extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /**
     * Translatable editorial fields (stored as JSON, one key per locale).
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'hero_title',
        'hero_subtitle',
        'hero_role',
        'hero_location',
        'hero_exp',
        'hero_focus',
        'about_body',
        'contact_lead',
    ];

    /**
     * The editorial singleton: the one and only row, created on demand.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
