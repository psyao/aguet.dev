<?php

namespace App\ContentTypes;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Psyao\Kami\Content\ContentType;
use Psyao\Kami\Content\Entry;
use Psyao\Kami\Content\Fields\Email;
use Psyao\Kami\Content\Fields\RichText;
use Psyao\Kami\Content\Fields\Text;
use Psyao\Kami\Content\Fields\Textarea;
use Psyao\Kami\Content\Fields\Url;
use Psyao\Kami\Content\Singleton;
use Symfony\Component\HttpFoundation\Response;

/**
 * The one-page Site: its one Entry is the Home Page, served at "/" in French and
 * at "/en" through its English Translation. Its Fields are those of Kami's
 * `tests/Install/AguetDevTest.php`, `about_body` a RichText.
 */
final class Home extends Singleton
{
    #[\Override]
    protected ?string $prefix = '';

    public function fields(): array
    {
        return [
            Textarea::make('hero_title')->inlineMarkdown(),
            Text::make('hero_subtitle')->inlineMarkdown(),
            Text::make('hero_role'),
            Text::make('hero_location'),
            Text::make('hero_exp'),
            Text::make('hero_focus'),
            RichText::make('about_body'),
            Text::make('contact_lead')->inlineMarkdown(),
            Email::make('contact_email')->shared(),
            Url::make('contact_linkedin')->shared(),
            Text::make('contact_linkedin_label')->shared(),
            Url::make('contact_github')->shared(),
            Text::make('contact_github_label')->shared(),
        ];
    }

    /**
     * aguet's own home view, its language switch built from the Entry's URL in each Locale.
     */
    #[\Override]
    public function render(Entry $entry): View|Response
    {
        $homeUrls = collect(array_keys(config('kami.sites.default.locales')))
            ->mapWithKeys(fn (string $locale): array => [$locale => $entry->url($locale)])
            ->filter()
            ->all();

        return view('home', [
            'content' => $entry,
            'projects' => $this->listed(Project::class),
            'skills' => $this->listed(SkillGroup::class),
            'locale' => app()->getLocale(),
            'altUrl' => collect($homeUrls)->except(app()->getLocale())->first() ?? url('/'),
            'homeUrls' => $homeUrls,
        ]);
    }

    /**
     * A type's visible Entries in the Locale, in hand order, with their Tags.
     *
     * @param  class-string<ContentType>  $type
     * @return Collection<int, Entry>
     */
    private function listed(string $type): Collection
    {
        return Entry::query()->ofType($type)->ordered()->with('tags')->get();
    }
}
