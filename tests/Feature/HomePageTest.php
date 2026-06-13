<?php

namespace Tests\Feature;

use Database\Seeders\ProjectSeeder;
use Database\Seeders\SiteContentSeeder;
use Database\Seeders\SkillGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SiteContentSeeder::class, ProjectSeeder::class, SkillGroupSeeder::class]);
    }

    public function test_french_home_renders(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Développeur web full-stack', false);
        $response->assertSee('cvci', false);                 // project legend
        $response->assertSee('href="'.url('/en').'"', false); // language switch
    }

    public function test_english_home_renders(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('Full-stack web developer', false);
        $response->assertSee('lang="en"', false);
    }

    public function test_unknown_prefix_falls_back_to_default_locale(): void
    {
        // The default locale (FR) is served at "/", so a stray path 404s
        // rather than silently switching locale.
        $this->get('/nope')->assertNotFound();
    }

    public function test_skills_render_from_database(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Cœur Laravel', false);
        $response->assertSee('Filament', false);                       // a tag
        $response->assertSee('FR (natif) · EN (pro) · DE (notions)');  // languages sentence

        $this->get('/en')->assertSee('Laravel core', false);
    }

    public function test_project_tags_render_from_database_in_order(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // cvci's tags, distinctive to that card, in seeded position order.
        $response->assertSeeInOrder(['SSO Entra', 'Dataverse', 'a11y'], false);
    }

    public function test_contact_links_render_from_database(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('steve@aguet.dev', false);
        $response->assertSee('/in/steveaguet', false);
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
        // Progressive enhancement: the no-JS mailto fallback survives on the CTA.
        $response->assertSee('href="mailto:steve@aguet.dev"', false);
        $response->assertSee('$store.contact.open()', false);
    }

    public function test_contact_modal_renders_in_english(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('Send a message', false)
            ->assertSee('id="contact-modal-title"', false);
    }
}
