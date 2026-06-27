<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Content
{
    private const MD_OPTIONS = [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ];

    /**
     * Render an editorial headline edited as Markdown in Filament.
     * `*word*` becomes an accent <em>, `**word**` bold, newlines <br>.
     * No <p> wrapper (inline), so it is valid inside <h1>.
     */
    public static function heroTitle(?string $text): HtmlString
    {
        $html = Str::inlineMarkdown($text ?? '', self::MD_OPTIONS);

        return new HtmlString(nl2br($html, false));
    }

    /** Render a one-line field as inline Markdown (no <p> wrapper). */
    public static function md(?string $text): HtmlString
    {
        return new HtmlString(Str::inlineMarkdown($text ?? '', self::MD_OPTIONS));
    }
}
