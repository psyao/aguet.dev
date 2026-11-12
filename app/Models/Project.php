<?php

namespace App\Models;

use Psyao\Kami\Content\Entry;

/** An Entry of the Project Content Type. */
class Project extends Entry
{
    /** Host shown on the project card, derived from the website (e.g. "cvci.ch"). */
    public function host(): ?string
    {
        $website = $this->getAttribute('website');

        if (! $website) {
            return null;
        }

        $host = parse_url($website, PHP_URL_HOST) ?: $website;

        return preg_replace('/^www\./', '', $host);
    }
}
