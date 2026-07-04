<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable(
    'hero_title',
    'hero_subtitle',
    'hero_role',
    'hero_location',
    'hero_exp',
    'hero_focus',
    'about_body',
    'contact_lead',
)]
class SiteContent extends Model
{
    use HasTranslations;

    protected $fillable = [
        'hero_title',
        'hero_subtitle',
        'hero_role',
        'hero_location',
        'hero_exp',
        'hero_focus',
        'about_body',
        'contact_lead',
        'contact_email',
        'contact_linkedin',
        'contact_linkedin_label',
        'contact_github',
        'contact_github_label',
    ];

    /**
     * The editorial singleton: the one and only row, created on demand.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
