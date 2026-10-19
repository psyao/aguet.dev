<?php

namespace App\Models;

use Velox\Cms\Models\Entry;

/** An Entry of the Project Content Type. */
class Project extends Entry
{
    /** Host shown on the project card, derived from the URL (e.g. "cvci.ch"). */
    public function host(): ?string
    {
        $url = $this->getAttribute('url');

        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        return preg_replace('/^www\./', '', $host);
    }
}
