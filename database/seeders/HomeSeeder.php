<?php

namespace Database\Seeders;

use App\ContentTypes\Home;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Velox\Cms\Models\Entry;

class HomeSeeder extends Seeder
{
    /**
     * The Home Page: its French Origin and English Translation (FR from the
     * brief; EN translated). hero_title uses the *word* = accent emphasis and
     * \n = line-break convention rendered by the front end; about_body is
     * written as Markdown here and stored as a rich-text document.
     */
    public function run(): void
    {
        if (Entry::homePage() !== null) {
            return;
        }

        $translated = [
            'hero_title' => [
                'fr' => "Développeur web *PHP*,\nspécialiste *backend*.",
                'en' => "*PHP* web developer,\n*backend* focus.",
            ],
            'hero_subtitle' => [
                'fr' => 'Je conçois, développe et fais évoluer des plateformes Laravel sur mesure.',
                'en' => 'I design, build and evolve custom Laravel platforms.',
            ],
            'hero_role' => [
                'fr' => 'Développeur web full-stack — région lémanique',
                'en' => 'Full-stack web developer — Lake Geneva region',
            ],
            'hero_location' => [
                'fr' => 'Région lémanique · Suisse',
                'en' => 'Lake Geneva region · Switzerland',
            ],
            'hero_exp' => [
                'fr' => '15+ ans d’expérience',
                'en' => '15+ years of experience',
            ],
            'hero_focus' => [
                'fr' => 'Spécialisé Laravel',
                'en' => 'Laravel specialist',
            ],
            'about_body' => [
                'fr' => <<<'MD'
                    Je développe pour le web depuis plus de quinze ans. J’ai commencé à une époque où « faire un site » voulait dire toucher à tout, et cette polyvalence m’est restée. Avec le temps, mon terrain s’est précisé : aujourd’hui je suis développeur full-stack à dominante back-end, et Laravel est l’outil avec lequel je construis le mieux.

                    Selon les projets, je suis seul aux commandes du développement, ou intégré à une équipe où je tiens la partie back-end. Il m’arrive aussi de reprendre des bases de code existantes pour les maintenir et les faire évoluer. Ce qui me motive : transformer un besoin métier complexe en une application solide et maintenable — de celles qui tournent sans bruit une fois livrées.
                    MD,
                'en' => <<<'MD'
                    I’ve been building for the web for over fifteen years. I started at a time when “making a website” meant touching everything, and that versatility stuck with me. Over time my focus sharpened: today I’m a full-stack developer with a back-end emphasis, and Laravel is the tool I build best with.

                    Depending on the project, I’m either solo at the controls or embedded in a team where I own the back end. I also take over existing codebases to maintain and grow them. What drives me: turning a complex business need into a solid, maintainable application — the kind that runs quietly once it’s shipped.
                    MD,
            ],
            'contact_lead' => [
                'fr' => 'Un projet Laravel, une plateforme à reprendre ou à faire évoluer ?',
                'en' => 'A Laravel project, a platform to take over or to grow?',
            ],
        ];

        // Untranslated Fields: entered once, on the Origin.
        $shared = [
            'contact_email' => 'steve@aguet.dev',
            'contact_linkedin' => 'https://www.linkedin.com/in/steveaguet',
            'contact_linkedin_label' => '/in/steveaguet',
            'contact_github' => 'https://github.com/psyao',
            'contact_github_label' => '/psyao',
        ];

        $translated['about_body'] = array_map(
            fn (string $markdown): array => RichContentRenderer::make(Str::markdown($markdown))->toArray(),
            $translated['about_body'],
        );

        $home = Entry::create([
            'type' => (new Home)->handle(),
            'title' => 'Accueil',
            'status' => 'published',
            'published_at' => now(),
            'data' => [...array_map(fn (array $value) => $value['fr'], $translated), ...$shared],
        ]);

        $home->translate('en', [
            'title' => 'Home',
            'status' => 'published',
            'published_at' => now(),
            'data' => array_map(fn (array $value) => $value['en'], $translated),
        ]);

        Entry::setHomePage($home);
    }
}
