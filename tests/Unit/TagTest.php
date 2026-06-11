<?php

namespace Tests\Unit;

use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_trimmed_and_inner_whitespace_collapsed(): void
    {
        $tag = Tag::create(['name' => "  Laravel   Cloud \t"]);

        $this->assertSame('Laravel Cloud', $tag->name);
    }

    public function test_name_is_unique(): void
    {
        Tag::create(['name' => 'Laravel']);

        $this->expectException(QueryException::class);
        Tag::create(['name' => 'Laravel']);
    }

    public function test_sanitize_names_filters_and_dedupes_case_insensitively(): void
    {
        $result = Tag::sanitizeNames([
            ' Laravel ',
            'laravel',      // duplicate, different case
            '',
            '   ',
            42,             // non-string
            null,           // non-string
            'API',
            'Laravel',      // exact duplicate
        ]);

        $this->assertSame(['Laravel', 'API'], $result);
    }
}
