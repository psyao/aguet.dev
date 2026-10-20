<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\HomeSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SkillGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Velox\Cms\Models\Entry;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([HomeSeeder::class, ProjectSeeder::class, SkillGroupSeeder::class]);
    }

    public function test_french_home_renders(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Développeur web full-stack', false);
        $response->assertSee('cvci', false);                 // project legend
        $response->assertSee('href="'.url('/en').'"', false); // language switch
    }

    public function test_french_home_renders_the_home_entry_texts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Développeur web <em>PHP</em>,<br>', false)
            ->assertSee('Je conçois, développe et fais évoluer des plateformes Laravel sur mesure.', false)
            ->assertSee('<p>Je développe pour le web depuis plus de quinze ans.', false)
            ->assertSee('Un projet Laravel, une plateforme à reprendre ou à faire évoluer ?', false)
            ->assertSee('Région lémanique · Suisse', false);
    }

    public function test_english_home_renders_the_home_translation_texts(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('<em>PHP</em> web developer,<br>', false)
            ->assertSee('<p>I’ve been building for the web for over fifteen years.', false)
            ->assertSee('A Laravel project, a platform to take over or to grow?', false)
            ->assertSee('/in/steveaguet', false)                     // untranslated, read from the Origin
            ->assertSee(base64_encode('steve@aguet.dev'), false);
    }

    public function test_language_switch_goes_between_both_homes(): void
    {
        $this->get('/en')
            ->assertSee('href="'.url('/').'" hreflang="fr"', false)  // chrome switch
            ->assertSee('<a class="seg r" href="'.url('/').'"', false); // statusbar switch

        $this->get('/')->assertSee('<a class="seg r" href="'.url('/en').'"', false);
    }

    public function test_english_home_renders(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('Full-stack web developer', false);
        $response->assertSee('lang="en"', false);
    }

    public function test_home_sends_a_content_security_policy(): void
    {
        $response = $this->get('/');

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('static.cloudflareinsights.com', $csp);
        $this->assertStringContainsString('cloudflareinsights.com', $csp);
    }

    public function test_home_is_not_marked_no_store(): void
    {
        // The contact-form Livewire component otherwise makes Livewire stamp the
        // page "no-store", which disables the browser back-forward cache.
        $response = $this->get('/');

        $this->assertStringNotContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
        );
    }

    public function test_an_unknown_path_answers_aguets_404_page(): void
    {
        // The default locale (FR) is served at "/", so a stray path 404s
        // rather than silently switching locale.
        $this->get('/nope')->assertNotFound()->assertSee('404 : route introuvable');
    }

    public function test_head_carries_the_home_pages_seo_in_each_language(): void
    {
        $heads = [
            '/' => [
                'title' => 'Steve Aguet — Développeur web full-stack Laravel',
                'description' => 'Steve Aguet, développeur web full-stack à dominante back-end, spécialisé Laravel — région lémanique. Intégration &amp; automatisation, plateformes sur mesure.',
                'url' => url('/'),
                'locale' => 'fr_CH',
                'alternate' => 'en_GB',
            ],
            '/en' => [
                'title' => 'Steve Aguet — Full-stack Laravel web developer',
                'description' => 'Steve Aguet, full-stack web developer with a back-end focus, Laravel specialist — Lake Geneva region. Integration &amp; automation, custom platforms.',
                'url' => url('/en'),
                'locale' => 'en_GB',
                'alternate' => 'fr_CH',
            ],
        ];

        foreach ($heads as $path => $head) {
            $html = $this->get($path)->assertOk()->getContent();

            foreach ([
                '<title>'.$head['title'].'</title>',
                '<meta name="description" content="'.$head['description'].'">',
                '<link rel="canonical" href="'.$head['url'].'">',
                '<link rel="alternate" hreflang="fr" href="'.url('/').'">',
                '<link rel="alternate" hreflang="en" href="'.url('/en').'">',
                '<link rel="alternate" hreflang="x-default" href="'.url('/').'">',
                '<meta property="og:type" content="website">',
                '<meta property="og:site_name" content="aguet.dev">',
                '<meta property="og:title" content="'.$head['title'].'">',
                '<meta property="og:description" content="'.$head['description'].'">',
                '<meta property="og:url" content="'.$head['url'].'">',
                '<meta property="og:locale" content="'.$head['locale'].'">',
                '<meta property="og:locale:alternate" content="'.$head['alternate'].'">',
                '<meta name="twitter:card" content="summary">',
            ] as $tag) {
                $this->assertSame(1, substr_count($html, $tag), "{$path}: {$tag}");
            }

            $this->assertSame(1, substr_count($html, '<title>'), "{$path}: one title");
            $this->assertStringNotContainsString('og:image', $html);
        }
    }

    public function test_sitemap_lists_both_homes_with_their_alternates_and_nothing_else(): void
    {
        $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->assertSame([url('/'), url('/en')], array_map('strval', $xml->xpath('//s:url/s:loc')));

        foreach ($xml->xpath('//s:url') as $url) {
            $url->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
            $alternates = [];
            foreach ($url->xpath('x:link') as $link) {
                $alternates[(string) $link['hreflang']] = (string) $link['href'];
            }
            ksort($alternates);

            $this->assertSame(['en' => url('/en'), 'fr' => url('/'), 'x-default' => url('/')], $alternates);
        }
    }

    public function test_robots_keeps_the_admin_out_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_robots_keeps_everything_out_elsewhere(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee("Disallow: /\n", false)
            ->assertDontSee('Disallow: /admin');
    }

    public function test_projects_show_featured_first_then_in_hand_order_in_both_languages(): void
    {
        Entry::arrange(Project::query()->inLocale('fr')->whereIn('slug', ['vaud-rando', 'animalia'])->orderByDesc('slug')->pluck('id')->all());

        foreach (['/', '/en'] as $url) {
            $this->get($url)->assertOk()->assertSeeInOrder(
                array_map($this->legend(...), ['cvci', 'vaud-rando', 'atr-booking', 'cvci-125', 'montreux-noel-2023', 'terre-et-nature', 'animalia']),
                false,
            );
        }
    }

    public function test_a_draft_project_is_absent(): void
    {
        Project::query()->inLocale('fr')->where('slug', 'animalia')->sole()->revertToDraft();

        $this->get('/')->assertOk()->assertDontSee($this->legend('animalia'), false);
    }

    public function test_a_project_card_reads_its_origins_slug_in_english(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee($this->legend('cvci-125'), false)
            ->assertDontSee($this->legend('cvci-125-ans'), false)
            ->assertSee('On-site interactive quiz for the CVCI\'s 125th anniversary', false)
            ->assertSee('<b>terrenature.ch</b>', false); // host() of the URL shared with the Origin
    }

    public function test_project_cards_render_their_texts_in_each_language(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Chambre vaudoise du commerce et de l’industrie', false)
            ->assertSee('Full stack / Backend heavy', false);

        $this->get('/en')->assertOk()
            ->assertSee('Vaud Chamber of Commerce and Industry', false)
            ->assertSee('Full stack / Back-end heavy', false);
    }

    public function test_project_tags_render_in_order_in_both_languages(): void
    {
        foreach (['/', '/en'] as $url) {
            // cvci's tags, in the order they were classified.
            $this->get($url)->assertOk()->assertSeeInOrder(
                ['<span>Laravel</span><span>MS Entra</span><span>MS Graph</span><span>Livewire</span><span>Blade</span><span>Filament</span><span>Tailwind CSS</span>'],
                false,
            );
        }
    }

    public function test_skills_tree_renders_in_both_languages(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Cœur Laravel', '(6)', '<span>PHP</span><span>Laravel</span><span>Filament</span><span>Eloquent</span><span>Blade</span><span>Livewire</span>'], false)
            ->assertSeeInOrder(['Intégration &amp; automatisation', 'Bases de données', 'Frontend', 'Langues'], false)
            ->assertSee('<span>FR (natif) · EN (pro) · DE (notions)</span>', false)
            ->assertSee('<b>5</b> '.__('site.skills.groups_word', [], 'fr').' · <b>16</b> '.__('site.skills.tech_word', [], 'fr').' · là où je fais la différence ▸ <span class="accent">Intégration &amp; automatisation</span>', false);

        $this->get('/en')->assertOk()
            ->assertSeeInOrder(['Laravel core', '(6)', '<span>PHP</span><span>Laravel</span><span>Filament</span><span>Eloquent</span><span>Blade</span><span>Livewire</span>'], false)
            ->assertSeeInOrder(['Integration &amp; automation', 'Databases', 'Front-end', 'Languages'], false)
            ->assertSee('<span>FR (native) · EN (professional) · DE (basics)</span>', false)
            ->assertSee('<b>5</b> '.__('site.skills.groups_word', [], 'en').' · <b>16</b> '.__('site.skills.tech_word', [], 'en').' · where I make the difference ▸ <span class="accent">Integration &amp; automation</span>', false);
    }

    public function test_command_palette_lists_the_projects_in_both_languages(): void
    {
        foreach (['/', '/en'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, preg_match('#window\.__AGUET = (.*?);</script>#s', $html, $m));
            $projects = json_decode($m[1], true)['projects'];

            $this->assertSame(['label' => 'CVCI', 'host' => 'cvci.ch', 'url' => 'https://cvci.ch'], $projects[0]);
        }
    }

    private function legend(string $slug): string
    {
        return '<span class="slash">~/projects/</span><b>'.$slug.'</b>';
    }

    public function test_contact_links_render_from_database(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/in/steveaguet', false);   // LinkedIn stays plaintext
    }

    public function test_email_is_not_exposed_as_plaintext(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // The address is never in the source as plaintext or a mailto: link;
        // it ships base64-encoded and is rebuilt by JS at runtime.
        $response->assertDontSee('steve@aguet.dev', false);
        $response->assertDontSee('mailto:steve@aguet.dev', false);
        $response->assertSee(base64_encode('steve@aguet.dev'), false);
    }

    public function test_contact_modal_and_livewire_form_render(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // Accessible dialog shell + visible title.
        $response->assertSee('id="contact-modal"', false);
        $response->assertSee('aria-labelledby="contact-modal-title"', false);
        $response->assertSee('Écrire un message', false);
        // The Livewire form mounted (its fields are present).
        $response->assertSee('id="cf-subject"', false);
        // CTA opens the modal with JS; its no-JS fallback is LinkedIn, not a
        // mailto: (keeps the email out of the source).
        $response->assertSee('$store.contact.open()', false);
        $response->assertDontSee('href="mailto:', false);
    }

    public function test_contact_modal_renders_in_english(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('Send a message', false)
            ->assertSee('id="contact-modal-title"', false);
    }

    public function test_homepage_includes_person_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(
            1,
            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches),
            'Expected exactly one JSON-LD script block on the homepage.',
        );

        $schema = json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('Person', $schema['@type']);
        $this->assertSame('Steve Aguet', $schema['name']);
        $this->assertSame('Développeur web full-stack — région lémanique', $schema['jobTitle']);
        $this->assertSame(url('/'), $schema['url']);
        $this->assertIsList($schema['sameAs']);
        $this->assertSame([
            'https://www.linkedin.com/in/steveaguet',
            'https://github.com/psyao',
        ], $schema['sameAs']);

        $this->assertArrayNotHasKey('email', $schema);
        $this->assertArrayNotHasKey('image', $schema);
        $this->assertArrayNotHasKey('address', $schema);

        // JSON-LD addition must not leak the plaintext address the base64
        // obfuscation elsewhere on the page was written to hide.
        $response->assertDontSee('steve@aguet.dev', false);
        $response->assertDontSee('mailto:steve@aguet.dev', false);
    }

    public function test_homepage_person_json_ld_resolves_current_locale(): void
    {
        $response = $this->get('/en');

        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(
            1,
            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches),
            'Expected exactly one JSON-LD script block on the homepage.',
        );

        $schema = json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Full-stack web developer — Lake Geneva region', $schema['jobTitle']);
        $this->assertSame(url('/en'), $schema['url']);
    }
}
