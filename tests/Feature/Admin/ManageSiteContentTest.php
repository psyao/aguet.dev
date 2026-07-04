<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\ManageSiteContent;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageSiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_persists_every_field_including_both_locales(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSiteContent::class)
            ->fillForm([
                'hero_title' => ['fr' => 'Titre FR', 'en' => 'Title EN'],
                'hero_subtitle' => ['fr' => 'Sous-titre FR', 'en' => 'Subtitle EN'],
                'hero_role' => ['fr' => 'Dev FR', 'en' => 'Dev EN'],
                'hero_location' => ['fr' => 'Suisse', 'en' => 'Switzerland'],
                'hero_exp' => ['fr' => '10 ans', 'en' => '10 years'],
                'hero_focus' => ['fr' => 'Web FR', 'en' => 'Web EN'],
                'about_body' => ['fr' => 'À propos FR', 'en' => 'About EN'],
                'contact_lead' => ['fr' => 'Accroche FR', 'en' => 'Lead EN'],
                'contact_email' => 'me@example.com',
                'contact_linkedin' => 'https://linkedin.com/in/x',
                'contact_linkedin_label' => '/in/x',
                'contact_github' => 'https://github.com/x',
                'contact_github_label' => '/x',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $content = SiteContent::current();

        $this->assertStringContainsString('Title EN', $content->getTranslation('hero_title', 'en'));
        $this->assertStringContainsString('Sous-titre FR', $content->getTranslation('hero_subtitle', 'fr'));
        $this->assertSame('Dev EN', $content->getTranslation('hero_role', 'en'));
        $this->assertSame('Suisse', $content->getTranslation('hero_location', 'fr'));
        $this->assertSame('10 years', $content->getTranslation('hero_exp', 'en'));
        $this->assertSame('Web FR', $content->getTranslation('hero_focus', 'fr'));
        $this->assertStringContainsString('About EN', $content->getTranslation('about_body', 'en'));
        $this->assertStringContainsString('Accroche FR', $content->getTranslation('contact_lead', 'fr'));
        $this->assertSame('me@example.com', $content->contact_email);
        $this->assertSame('https://linkedin.com/in/x', $content->contact_linkedin);
        $this->assertSame('/in/x', $content->contact_linkedin_label);
        $this->assertSame('https://github.com/x', $content->contact_github);
        $this->assertSame('/x', $content->contact_github_label);
    }
}
