<?php

namespace Tests\Unit;

use App\Support\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function test_hero_title_converts_bold_to_accent_and_newline_to_break(): void
    {
        $html = (string) Content::heroTitle("Développeur,\nà dominante **back-end**.");

        $this->assertStringContainsString('<br', $html);
        $this->assertStringContainsString('<em>back-end</em>', $html);
    }

    public function test_hero_title_escapes_html(): void
    {
        $html = (string) Content::heroTitle('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_hero_title_handles_null(): void
    {
        $this->assertSame('', (string) Content::heroTitle(null));
    }
}
