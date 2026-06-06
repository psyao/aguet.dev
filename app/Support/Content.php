<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Content
{
    /**
     * Render an editorial headline edited as plain text in Filament.
     * Convention: **word** becomes an accent <em>, and newlines become <br>.
     * The input is escaped first, so it is safe to echo as raw HTML.
     */
    public static function heroTitle(?string $text): HtmlString
    {
        $html = e($text ?? '');
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<em>$1</em>', $html);
        $html = nl2br($html, false);

        return new HtmlString($html);
    }
}
