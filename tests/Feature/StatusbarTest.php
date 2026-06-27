<?php

namespace Tests\Feature;

use Database\Seeders\ProjectSeeder;
use Database\Seeders\SiteContentSeeder;
use Database\Seeders\SkillGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SiteContentSeeder::class, ProjectSeeder::class, SkillGroupSeeder::class]);
        config(['aguet.repo_url' => 'https://github.com/psyao/aguet.dev']);
    }

    public function test_pill_is_a_palette_button(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('class="seg mode"', false)
            ->assertSee('$store.cmdk.toggle()', false);
    }

    public function test_valid_sha_renders_commit_link(): void
    {
        $sha = str_repeat('a1b2c3d4', 5); // 40 hex chars
        config([
            'build_info.sha' => $sha,
            'build_info.message_b64' => base64_encode('feat: ship the thing'),
            'build_info.date' => '2026-06-20T10:00:00+02:00',
        ]);

        $res = $this->get('/')->assertOk();
        $res->assertSee('href="https://github.com/psyao/aguet.dev/commit/'.$sha.'"', false);
        $res->assertSee('rel="noopener noreferrer"', false);
        $res->assertSee('feat: ship the thing', false);
    }

    public function test_invalid_sha_falls_back_to_repo_link_only(): void
    {
        config(['build_info.sha' => 'not-a-sha', 'build_info.message_b64' => null]);

        $res = $this->get('/')->assertOk();
        $res->assertDontSee('/commit/', false);
        $res->assertSee('href="https://github.com/psyao/aguet.dev"', false);
    }

    public function test_commit_subject_is_escaped(): void
    {
        config([
            'build_info.sha' => str_repeat('f', 40),
            'build_info.message_b64' => base64_encode('<script>x</script>'),
        ]);

        $res = $this->get('/')->assertOk();
        $res->assertDontSee('<script>x</script>', false); // raw tag must not appear
        $res->assertSee('&lt;script&gt;x&lt;/script&gt;', false); // {{ }} escapes it
    }

    public function test_screenshot_mode_has_no_alpine(): void
    {
        // PHPUnit boots with APP_ENV=testing (phpunit.xml), so $shot activates
        // on ?screenshot=1 — no need to set app.env here (it's a no-op post-boot).
        $this->get('/?screenshot=1')
            ->assertOk()
            ->assertDontSee('x-data="statusbar"', false);
    }

    public function test_exposes_vim_easter_egg_i18n_keys(): void
    {
        foreach (['/', '/en'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Extract the inline `window.__AGUET = {...};</script>` payload.
            $this->assertSame(1, preg_match('#window\.__AGUET = (.*?);</script>#s', $html, $m));
            $cfg = json_decode($m[1], true);

            foreach (['cmd.wq', 'help.motions', 'help.jumps', 'help.excmd', 'help.konami'] as $key) {
                $val = $cfg['i18n'][$key] ?? '';
                $this->assertNotSame('', $val, "missing i18n key {$key} for {$url}");
                // A missing lang entry makes __('site.x') return the literal
                // 'site.x' — non-empty, so guard against that false pass too.
                $this->assertNotSame("site.{$key}", $val, "untranslated i18n key {$key} for {$url}");
            }
        }
    }

    public function test_renders_vim_command_line_echo_segment(): void
    {
        $this->get('/')->assertOk()->assertSee('x-text="$store.vim.msg"', false);
    }

    public function test_screenshot_mode_omits_echo_segment(): void
    {
        $this->get('/?screenshot=1')->assertOk()->assertDontSee('x-text="$store.vim.msg"', false);
    }

    public function test_palette_empty_state_hidden_in_command_mode(): void
    {
        $this->get('/')->assertOk()->assertSee('!$store.cmdk.commandMode', false);
    }
}
