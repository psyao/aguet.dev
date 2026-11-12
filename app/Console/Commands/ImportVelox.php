<?php

namespace App\Console\Commands;

use App\ContentTypes\Home;
use App\ContentTypes\Project;
use App\ContentTypes\SkillGroup;
use App\Forms\ContactForm;
use App\Models\User;
use App\Taxonomies\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psyao\Kami\Content\ContentType;
use Psyao\Kami\Content\Entry;
use Psyao\Kami\Content\SiteSettings;
use Psyao\Kami\Content\Term;
use Psyao\Kami\Forms\Submission;
use Psyao\Kami\Import\Import;
use Psyao\Kami\Media\Media;
use RuntimeException;

/**
 * Copies aguet.dev from Velox's tables into Kami at the Switch-over: Tags, Media, the SEO settings, the Entries,
 * the contact messages and the administrators' MFA, then checks every count against the source.
 *
 * ponytail: one pass over the Entries. aguet's Entries link only to Tags and Media, imported before them,
 * so `find()` resolves every link on the first pass; a second pass belongs here once an Entry links to another.
 */
#[Signature('app:import:velox {--again : Replay over an earlier import. The source wins over every edit made in Kami since, and a message erased in Kami since (retention or an erasure request) is copied again}')]
#[Description('Imports aguet.dev from Velox into Kami, then checks the counts')]
final class ImportVelox extends Command
{
    /** @var array<string, class-string<ContentType>> Velox's type handles. */
    private const array TYPES = ['home' => Home::class, 'project' => Project::class, 'skill-group' => SkillGroup::class];

    /** @var list<class-string<ContentType>> The types holding Tags, in hand order. */
    private const array LISTED = [Project::class, SkillGroup::class];

    /** @var array<int, string> Kami's Media id for each Velox Media item id. */
    private array $media = [];

    public function handle(): int
    {
        if (! $this->option('again') && DB::table('kami_import_keys')->where('source', 'like', 'velox.%')->exists()) {
            $this->info('Velox is imported already: nothing to do. Replay with --again.');

            return self::SUCCESS;
        }

        $users = DB::table('users')->count();
        $import = Import::start('Velox');
        $this->importTags($import);
        $this->importMedia($import);
        $this->importSettings($import);
        $this->importEntries($import);
        $this->importMessages($import);
        $report = $import->finish();
        $this->importMfa();

        $this->info("{$report->created} created, {$report->updated} updated, {$report->unchanged} unchanged, {$report->unseen} unseen.");

        foreach ($report->unseenKeys as $key) {
            $this->warn("Gone from Velox, left in Kami: [{$key['source']}] item [{$key['legacy_id']}].");
        }

        return $this->countsMatch($users) ? self::SUCCESS : self::FAILURE;
    }

    private function importTags(Import $import): void
    {
        DB::table('velox_terms')->where('taxonomy', 'tag')->whereNull('origin_id')->orderBy('id')->each(function (object $term) use ($import): void {
            $import->entry('velox.term', (string) $term->id, Tag::class, [$this->defaultLocale() => ['title' => $term->name]], $this->date($term->created_at), $this->date($term->updated_at));
        });
    }

    private function importMedia(Import $import): void
    {
        DB::table('velox_media_items')->orderBy('id')->each(function (object $item) use ($import): void {
            $alt = json_decode((string) $item->alt, true);
            $values = array_map(fn (string $text): array => ['alt' => $text], $this->perLocale(is_array($alt) ? $alt : $item->alt));

            $this->media[$item->id] = $import->media('velox.media', $item->ulid, Storage::disk($item->disk)->path($item->path), $values, $this->date($item->created_at), $this->date($item->updated_at))->id;
        });
    }

    /** Velox's SEO settings page: the Site's name and description per Locale, and its default social image. */
    private function importSettings(Import $import): void
    {
        $row = DB::table('velox_settings')->where('key', 'seo')->first();

        if ($row === null) {
            return;
        }

        $seo = json_decode($row->value, true, flags: JSON_THROW_ON_ERROR);
        $names = $this->perLocale($seo['site_name'] ?? null);
        $descriptions = $this->perLocale($seo['description'] ?? null);
        $values = [];

        foreach ($this->locales() as $locale) {
            $values[$locale] = array_filter(['site_name' => $names[$locale] ?? null, 'default_description' => $descriptions[$locale] ?? null]);
        }

        $values[$this->defaultLocale()]['default_social_image'] = $this->media[(int) ($seo['image'] ?? 0)] ?? null;

        $import->entry('velox.setting', 'seo', SiteSettings::class, array_filter($values), $this->date($row->created_at), $this->date($row->updated_at));
    }

    /** Each Velox Origin with its Translations as one item, its Tags resolved through the Terms imported before. */
    private function importEntries(Import $import): void
    {
        DB::table('velox_entries')->whereNull('origin_id')->whereNull('deleted_at')->orderBy('id')->each(function (object $origin) use ($import): void {
            $type = self::TYPES[$origin->type] ?? throw new RuntimeException("Velox type [{$origin->type}] has no Kami type.");
            $rows = DB::table('velox_entries')->where('origin_id', $origin->id)->whereNull('deleted_at')->get()->prepend($origin)->keyBy('locale');
            $values = [];

            foreach ($this->locales() as $locale) {
                if ($rows->has($locale)) {
                    $values[$locale] = $this->entryValues($type, $rows[$locale]);
                }
            }

            if ($type === Project::class) {
                // The card's legend, which Velox read from the slug.
                $values[$origin->locale]['directory'] = $origin->slug;
            }

            if (in_array($type, self::LISTED, true)) {
                $values[$origin->locale]['tags'] = DB::table('velox_entry_term')->where('entry_id', $origin->id)->orderBy('position')->pluck('term_id')
                    ->map(fn (int $term): ?string => $import->find('velox.term', (string) $term)?->id)
                    ->filter()
                    ->values()
                    ->all();
            }

            $import->entry('velox.entry', (string) $origin->id, $type, $values, $this->date($origin->created_at), $this->date($rows->max('updated_at')));
        });
    }

    /**
     * One Velox row's values in its Locale, as `SaveEntry` takes them.
     *
     * @param  class-string<ContentType>  $type
     * @return array<string, mixed>
     */
    private function entryValues(string $type, object $row): array
    {
        $data = json_decode($row->data ?? '[]', true, flags: JSON_THROW_ON_ERROR) ?: [];
        $seo = json_decode($row->seo ?? '[]', true, flags: JSON_THROW_ON_ERROR) ?: [];

        if (array_key_exists('url', $data)) {
            $data['website'] = $data['url'];
            unset($data['url']);
        }

        return [
            'title' => $row->title,
            'status' => $row->status,
            'published_at' => $row->published_at,
            ...(in_array($type, self::LISTED, true) ? ['position' => $row->position] : []),
            ...$data,
            ...array_filter([
                'meta_title' => $seo['title'] ?? null,
                'meta_description' => $seo['description'] ?? null,
                'noindex' => $seo['noindex'] ?? null,
                'seo_image' => isset($seo['image']) ? $this->media[(int) $seo['image']] ?? null : null,
            ], fn (mixed $value): bool => $value !== null),
        ];
    }

    /** The contact messages, in the default Locale: Velox stored none. */
    private function importMessages(Import $import): void
    {
        $form = resolve(ContactForm::class);

        DB::table('contact_messages')->orderBy('id')->each(function (object $message) use ($import, $form): void {
            $import->submission($form, [
                'subject' => $message->subject,
                'email' => $message->email,
                'message' => $message->message,
            ], $this->defaultLocale(), $this->date($message->created_at), $this->date($message->read_at));
        });
    }

    /** Velox kept MFA on `users`, encrypted with the unchanged `APP_KEY`; Kami keeps it in its own table, and the `users` row is left as it is. */
    private function importMfa(): void
    {
        DB::table('users')->whereNotNull('app_authentication_secret')->orderBy('id')->each(function (object $row): void {
            $user = User::query()->findOrFail($row->id);
            $user->saveAppAuthenticationSecret(Crypt::decryptString($row->app_authentication_secret));
            $user->saveAppAuthenticationRecoveryCodes($row->app_authentication_recovery_codes === null
                ? null
                : json_decode(Crypt::decryptString($row->app_authentication_recovery_codes), true, flags: JSON_THROW_ON_ERROR));
        });
    }

    /** #28 decision 3: every count in Kami against Velox's, printed for the Switch-over's proof. */
    private function countsMatch(int $users): bool
    {
        $origins = fn (string $type) => DB::table('velox_entries')->where('type', $type)->whereNull('origin_id')->whereNull('deleted_at');
        $entries = fn (string $type) => Entry::query()->withUnpublished()->ofType($type);

        $counts = [
            'Home' => [$origins('home')->count(), $entries(Home::class)->count()],
            'Projects' => [$origins('project')->count(), $entries(Project::class)->count()],
            'SkillGroups' => [$origins('skill-group')->count(), $entries(SkillGroup::class)->count()],
            'Tags' => [DB::table('velox_terms')->where('taxonomy', 'tag')->whereNull('origin_id')->count(), Term::query()->withUnpublished()->ofType(Tag::class)->count()],
            'Classifications' => [
                DB::table('velox_entry_term')->whereIn('entry_id', DB::table('velox_entries')->whereNull('deleted_at')->select('id'))->count(),
                collect(self::LISTED)->sum(fn (string $type): int => $entries($type)->with('tags')->get()->sum(fn (Entry $entry): int => $entry->tags->count())),
            ],
            'Media' => [DB::table('velox_media_items')->count(), Media::query()->count()],
            'Messages' => [DB::table('contact_messages')->count(), Submission::query()->where('form', resolve(ContactForm::class)->handle())->count()],
            'Users' => [$users, DB::table('users')->count()],
        ];

        $this->table(['', 'Velox', 'Kami'], collect($counts)->map(fn (array $pair, string $name): array => [$name, ...$pair])->values()->all());
        $mismatches = collect($counts)->filter(fn (array $pair): bool => $pair[0] !== $pair[1])->keys();

        if ($mismatches->isNotEmpty()) {
            $this->error("Count mismatch: {$mismatches->join(', ')}.");

            return false;
        }

        $this->info('Counts match.');

        return true;
    }

    /**
     * A Velox value stored per Locale, or as a plain string meaning the default Locale's, without blanks.
     *
     * @param  array<string, ?string>|string|null  $value
     * @return array<string, string>
     */
    private function perLocale(array|string|null $value): array
    {
        $byLocale = is_array($value) ? $value : [$this->defaultLocale() => $value];

        return array_filter($byLocale, fn (?string $text): bool => filled($text));
    }

    /** @return list<string> The Site's Locales, the default one first. */
    private function locales(): array
    {
        return array_keys(config('kami.sites.default.locales'));
    }

    private function defaultLocale(): string
    {
        return $this->locales()[0];
    }

    private function date(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value, 'UTC');
    }
}
