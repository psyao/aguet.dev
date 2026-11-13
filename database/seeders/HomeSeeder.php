<?php

namespace Database\Seeders;

use App\ContentTypes\Home;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Psyao\Kami\Content\Actions\SaveEntry;
use Psyao\Kami\Content\Entry;
use Psyao\Kami\Content\Fields\RichText;
use Psyao\Kami\Content\RichText\Document;

class HomeSeeder extends Seeder
{
    /**
     * The Home Page: its French and English Translations (FR from the
     * brief; EN translated). hero_title uses the *word* = accent emphasis and
     * \n = line-break convention rendered by the front end; about_body is
     * written as Markdown here and stored as a RichText document.
     */
    public function run(): void
    {
        if (Home::entry() !== null) {
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

        // Shared Fields: one value for every Locale.
        $shared = [
            'contact_email' => 'steve@aguet.dev',
            'contact_linkedin' => 'https://www.linkedin.com/in/steveaguet',
            'contact_linkedin_label' => '/in/steveaguet',
            'contact_github' => 'https://github.com/psyao',
            'contact_github_label' => '/psyao',
        ];

        $translated['about_body'] = array_map(
            fn (string $markdown): Document => Document::fromHtml(Str::markdown($markdown), RichText::make('about_body')),
            $translated['about_body'],
        );

        // The page's Meta title and description, printed by <x-kami::seo />.
        $seo = [
            'fr' => [
                'title' => 'Steve Aguet — Développeur web full-stack Laravel',
                'description' => 'Steve Aguet, développeur web full-stack à dominante back-end, spécialisé Laravel — région lémanique. Intégration & automatisation, plateformes sur mesure.',
            ],
            'en' => [
                'title' => 'Steve Aguet — Full-stack Laravel web developer',
                'description' => 'Steve Aguet, full-stack web developer with a back-end focus, Laravel specialist — Lake Geneva region. Integration & automation, custom platforms.',
            ],
        ];

        $save = resolve(SaveEntry::class);
        // kami:install creates the Singleton as a Draft; a fresh test database has none yet.
        $home = Entry::query()->withUnpublished()->ofType(Home::class)->first();

        foreach (['fr' => 'Accueil', 'en' => 'Home'] as $locale => $title) {
            $home = $save->handle(Home::class, [
                'title' => $title,
                'status' => 'published',
                'meta_title' => $seo[$locale]['title'],
                'meta_description' => $seo[$locale]['description'],
                ...array_map(fn (array $value) => $value[$locale], $translated),
                ...($locale === 'fr' ? $shared : []),
            ], $home?->refresh(), $locale);
        }
    }
}
