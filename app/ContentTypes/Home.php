<?php

namespace App\ContentTypes;

use App\Models\Project;
use App\Models\SkillGroup;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;
use Velox\Cms\ContentType;
use Velox\Cms\Facades\Velox;
use Velox\Cms\Fields\RichText;
use Velox\Cms\Fields\Text;
use Velox\Cms\Fields\Textarea;
use Velox\Cms\LocaleLink;
use Velox\Cms\Models\Entry;

/**
 * The one-page Site: its one Entry is the Home Page, served at "/" in French and
 * at "/en" through its English Translation. The Fields keep the names of the
 * former SiteContent columns.
 */
class Home extends ContentType
{
    private const string INLINE_MARKDOWN = '*mot* = accent vert · **mot** = gras.';

    public function singularLabel(): string
    {
        return 'Page d’accueil';
    }

    public function pluralLabel(): string
    {
        return 'Accueil';
    }

    public function icon(): string
    {
        return 'heroicon-o-home';
    }

    public function fields(): array
    {
        return [
            Textarea::make('hero_title')->label('Titre')
                ->helpText(self::INLINE_MARKDOWN.' Un retour à la ligne = saut de ligne.'),
            Text::make('hero_subtitle')->label('Sous-titre')->helpText(self::INLINE_MARKDOWN),
            Text::make('hero_role')->label('Rôle (ligne « whoami »)'),
            Text::make('hero_location')->label('Localisation'),
            Text::make('hero_exp')->label('Expérience'),
            Text::make('hero_focus')->label('Focus'),
            RichText::make('about_body')->label('À propos'),
            Text::make('contact_lead')->label('Accroche du contact')->helpText(self::INLINE_MARKDOWN),
            Text::make('contact_email')->label('Email de contact')
                ->helpText('Reçoit les messages du formulaire de contact.')->translatable(false),
            Text::make('contact_linkedin')->label('LinkedIn (URL)')->translatable(false),
            Text::make('contact_linkedin_label')->label('LinkedIn (libellé affiché)')->translatable(false),
            Text::make('contact_github')->label('GitHub (URL)')->translatable(false),
            Text::make('contact_github_label')->label('GitHub (libellé affiché)')->translatable(false),
        ];
    }

    /**
     * aguet's own home view, its language switch built from Velox's locale links.
     */
    public function render(Entry $entry): Responsable|View|Response
    {
        $links = collect(Velox::locales());
        $alternate = $links->first(fn (LocaleLink $link) => ! $link->isCurrent);

        return view('home', [
            'content' => $entry,
            'projects' => Project::published()->with('tags')->get(),
            'skills' => SkillGroup::ordered()->with('tags')->get(),
            'locale' => app()->getLocale(),
            'altUrl' => $alternate?->url ?? url('/'),
            'homeUrls' => $links->mapWithKeys(fn (LocaleLink $link) => [$link->code => $link->url])->all(),
        ]);
    }
}
