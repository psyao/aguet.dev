# aguet.dev

Personal one-page portfolio for **Steve Aguet**, a full-stack (back-end-leaning) Laravel
developer in the Lake Geneva region. Bilingual (FR default, EN), self-editable through a
Filament admin panel. The site itself is meant as a demonstration of craft — the code is
public.

The visual direction is **"Terminal v2"**: a dark terminal/IDE aesthetic, monospace
throughout, with a typed boot intro, a ⌘K command palette, a tmux-style status bar, and a
clean light theme when printed to PDF.

## Contents

- [Stack](#stack) · [How it works](#how-it-works) · [Content model](#content-model)
- [Editing the site (Filament admin)](#editing-the-site-filament-admin) — the day-to-day
  owner workflow
- [Front-end architecture](#front-end-architecture) — Blade + Alpine, the terminal layer
- [Local setup](#local-setup) · [Deployment](#deployment--infomaniak-shared-hosting)
- Companion docs: [visual regression testing](docs/visual-testing.md) ·
  [CI deploy pipeline](DEPLOY.md)

## Stack

- **Laravel 13** · PHP 8.5+
- **Filament 5** — admin panel for editing content
- **spatie/laravel-translatable** + **outerweb/filament-translatable-fields** — per-locale
  JSON translations, edited as FR/EN tabs in Filament
- **MySQL**
- **Tailwind v4** + **Vite** — the terminal aesthetic is authored as custom CSS on top
- **Alpine.js** — bundled with Filament 5 / Livewire 4 (not installed separately); the
  front-end registers its components on `alpine:init`
- **JetBrains Mono** — self-hosted at build time via the Vite Bunny font helper
- **pnpm** for front-end tooling

## How it works

### Bilingual

- French is served at `/`, English at `/en`. The `SetLocale` middleware reads the locale
  from the URL and shares the alternate URL with views (used by the FR/EN switch and the
  `hreflang` tags). Adding a locale is just a `config/aguet.php` change.
- **UI chrome** strings (nav, buttons, labels) live in `lang/fr/site.php` + `lang/en/site.php`.
- **Editorial content** (hero, about, projects, contact) lives in the database, is
  translatable, and is edited in Filament — never in Git.

### Content model

Four tables back the editorial content. Translatable fields are stored as JSON, one key per
locale (spatie/laravel-translatable); everything else is shared across languages.

- **`SiteContent`** — the editorial singleton, fetched with `SiteContent::current()` (the
  one row, created on demand). Translatable: `hero_title`, `hero_subtitle`, `hero_role`,
  `hero_location`, `hero_exp`, `hero_focus`, `about_body` (Markdown), `contact_lead`.
  Language-neutral: `contact_email`, `contact_linkedin`, `contact_linkedin_label`,
  `contact_github`, `contact_github_label`. `hero_title` uses a small convention rendered by
  `App\Support\Content::heroTitle()`: `**word**` becomes a green accent emphasis, a newline
  becomes a line break.
- **`Project`** — the projects collection. Translatable: `name`, `client`, `role`,
  `summary`. Shared: `slug` (unique), `url`, `featured`, `sort_order`, `is_published`.
  `Project::published()` returns published rows ordered featured-first then `sort_order`;
  featured projects render full-width. `host()` derives the card's display host from `url`
  (strips `www.`). Tech tags come from the shared `Tag` table (below), not a column.
- **`SkillGroup`** — the rows of the `tree ~/stack` skills section, in `sort_order`
  (`SkillGroup::ordered()`). Translatable: `title`, `text`, `note`. A group renders its
  tags, unless `text` is set, which renders that sentence instead (e.g. the « Langues »
  group). `focus` flags the ★ group, highlighted in the tree, whose `note` feeds the tree
  footer.
- **`Tag`** — shared, non-translatable tech tags (« Laravel », « a11y », …), attached to
  both projects and skill groups through the `taggables` morph pivot. The pivot's `position`
  preserves a manual per-item tag order (the `HasTags` trait orders by it). Names are unique
  and normalized (trimmed, inner whitespace collapsed; max 50 chars).

> Earlier versions kept skills in a static `config/skills.php` and stored tags as
> `projects.stack[]` / `skill_groups.items[]` JSON columns. Both moved into the database —
> the `SkillGroup` model and the shared `Tag` table — so skills and tags are now editable in
> Filament without a deploy.

## Editing the site (Filament admin)

Everything editorial — hero, about, projects, skills, contact — is edited in the Filament
admin panel and stored in the database, never in Git. The panel is in French (it has one
user: the owner) and themed emerald to match the site.

### Sign in

Open **`/admin`** and sign in. The admin user is seeded by `AdminUserSeeder` from the
`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` values in `.env`; you can also create one
with `php artisan make:filament-user`. Translatable fields show **FR / EN** tabs (one per
locale) — fill both.

### Site content (« Contenu du site »)

The first nav item edits the `SiteContent` singleton, in three sections:

- **Hero** — `Titre`, `Sous-titre`, `Rôle`, `Localisation`, `Expérience`, `Focus`. In the
  title, `**mot**` renders the word as a green accent and a line break becomes a `<br>`.
- **À propos** — `Texte`, written in Markdown (leave a blank line between paragraphs).
- **Contact** — the lead line plus the email, LinkedIn and GitHub URLs and their displayed
  labels (e.g. `/in/steveaguet`). These feed the contact section and the ⌘K palette.

### Projects

The **Projects** resource is the collection. Each project has:

- **Contenu** (translatable): `Nom`, `Client`, `Rôle`, `Résumé`.
- **Métadonnées**: `Slug` (unique, auto-slugified as you type), `URL`, **Stack (tags)**,
  `Ordre de tri`, **Projet phare** (renders full-width, in front), **Publié**.

To feature a project, toggle **Projet phare**; to hide it, turn off **Publié**. Display
order is `Ordre de tri` ascending, with featured projects first.

### Skill groups

The **Skill Groups** resource drives the `tree ~/stack` section. Each group has a `Titre`,
a set of **Tags**, an `Ordre de tri`, and a **Groupe ★ (focus)** toggle. Leave the tags
empty and fill **Texte** instead to render a sentence rather than tags (used for the
« Langues » group). The focus group's **Note** shows in the tree footer.

### Tags

The **Tags** resource is the shared vocabulary used by both projects and skill groups.
Names are unique (case-insensitive, max 50 chars). You rarely open it directly: the
Stack/Tags picker on a project or skill group searches existing tags and lets you create a
new one inline, and the order you pick them in is the order they display.

## Front-end architecture

The site is server-rendered Blade with a thin Alpine.js layer for the terminal feel. It is
built as **progressive enhancement**: every section renders and every link works with
JavaScript disabled — the script only adds the boot animation, the live clock,
click-to-copy, the ⌘K palette, and the status bar's live vim-mode indicator.

### Request flow

`routes/web.php` serves the one page through `HomeController`, behind the `SetLocale`
middleware. `SetLocale` reads the locale from the first URL segment (default `fr` at `/`,
others under their own prefix, e.g. `/en`), calls `app()->setLocale()`, and shares `locale`,
`altUrl`, and the per-locale `homeUrls` with every view (used by the FR/EN switch and the
`hreflang` tags). The controller loads the `SiteContent` singleton plus published projects
and ordered skill groups, each with their tags eager-loaded.

### Layout and chrome

`layouts/app.blade.php` is the shell: window chrome (traffic-light dots, terminal title, the
⌘K button, the FR/EN switch), the section `tabs`, the `main` content, a tmux-style
`statusbar` (a reactive vim-mode pill that also opens the palette, a branch label with a
deployed-commit popover, a command-line echo, the live clock, locale, year), and the
command-palette markup. The current locale's UI strings and the palette data (projects,
contact links) are injected once as `window.__AGUET`, so the script stays locale-agnostic.

### Alpine, from Livewire

Alpine is **not** bundled separately. Filament 5 / Livewire 4 ships Alpine, starts it, and
exposes `window.Alpine`; `resources/js/app.js` registers its components on the `alpine:init`
event against that shared instance (no `import`, no `Alpine.start()`). The components:

- **`terminal`** (root) — kicks off the one-shot boot intro and the scrollspy that lights
  the active section tab.
- **`clock`** — the status-bar clock, `Europe/Zurich`, refreshed every 15 s.
- **`copy`** — click-to-copy a contact value, with a transient « copied » state and an
  `execCommand` fallback when the clipboard API is unavailable.
- **`statusbar`** — the tmux-style bottom bar. A reactive vim-mode pill reflects the current
  state (NORMAL / INSERT / VISUAL / COMMAND, derived from palette, text-selection and focus)
  and doubles as the palette trigger; the branch label opens a popover showing the deployed
  commit (short SHA → GitHub, subject, relative date) read from the generated
  `config/build_info.php` (written at deploy time, see [DEPLOY.md](DEPLOY.md)).
- **`$store.cmdk`** — the ⌘K command palette: it builds a grouped item list (navigation,
  projects, actions), fuzzy-filters it, and supports keyboard navigation. Typing `:` switches
  it into a vim-style command line (`:q`, `:wq`, `:help`, `:colorscheme`, …). A global `⌘K` / `Ctrl-K` listener
  toggles it; a bare `:` opens it straight into command mode.
- **`$store.vim`** — a transient command-line echo (vim-style messages) rendered in the
  status bar.

The page also rewards anyone who treats it like an actual editor — a few touches are left
unlabelled for the curious to find. Reading the page source is a fair place to start.

### The boot intro

The hero types itself out (`whoami`, then `cat headline.txt`) on first paint. An inline head
script adds `html.boot` to hide the hero up front, with a `2800 ms` failsafe that removes it
so content can never stay stuck hidden; the animation is skipped entirely under
`prefers-reduced-motion`. The CSS base state is **visible**, so no-JS, reduced-motion, and
print all render the final state directly.

### Theme tokens

The terminal palette lives in `resources/css/app.css` as Tailwind v4 `@theme static` tokens
(`--color-bg`, `--color-accent`, …). `static` matters: Tailwind v4 prunes theme vars that no
utility class references, and these are consumed by hand-written `var(--color-*)` CSS, not
utilities. The runtime font is a separate `--mono` var (it has no Tailwind default, so it
always wins). Three `data-*` knobs on `<body>` tune the look: `data-density`
(`comfortable` / `compact`), `data-fx` (`off` / `subtle` / `full` — the glow and scanline
intensity), and `data-theme` — the colorscheme. `data-theme` overrides the whole
`--color-*` block at runtime: the green phosphor base by default, plus curated `gruvbox`,
`nord`, `crt`, and `light` (Solarized) palettes, each hand-checked for WCAG AA on its text
tokens. It is set by the `:colorscheme <name>` command, persisted in `localStorage`, and
re-applied before paint by an inline layout script so a saved theme never flashes the
default first. The `crt` palette also drives `data-fx` (scanlines + glow).

### Screenshot mode

`?screenshot=1` (honored only under `APP_ENV=testing`) settles the page for the Pest visual
harness: it skips the boot intro, disables all animations and `backdrop-filter`, freezes the
clock and year, and re-asserts JetBrains Mono so captures are deterministic. It is inert in
production. See [docs/visual-testing.md](docs/visual-testing.md).

## Local setup

Requirements: PHP 8.5+, Composer, Node 24+, pnpm 11, a running MySQL server.

```bash
# 1. Install dependencies
composer install
pnpm install

# 2. Environment
cp .env.example .env
php artisan key:generate
# then edit .env: DB_* credentials and the ADMIN_* values (see below)

# 3. Create the database (Laravel's migrate does not create it for you)
mysql -h 127.0.0.1 -u root -e \
  "CREATE DATABASE IF NOT EXISTS aguet_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrate + seed  (schema + FR/EN content + admin user)
php artisan migrate --seed

# 5. Front-end assets
pnpm run build                    # or: pnpm run dev  (HMR during development)

# 6. Serve
php artisan serve                 # http://127.0.0.1:8000  (FR)  ·  /en  (EN)
```

**Shortcuts.** Once the database exists (step 3), `composer setup` runs the whole
install in one command (deps, `.env`, key, migrate, `pnpm install` + build). During
development, `composer dev` runs the server, queue, logs and Vite together.

The admin panel is at **`/admin`**. The admin user is created by `AdminUserSeeder` from the
`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` values in `.env`. (You can also create one
with `php artisan make:filament-user`.)

## Deployment — Infomaniak shared hosting

Shared hosting with SSH, Composer and cron is enough; no external services are required.

```bash
# On the server, in the project directory:
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force

# Front-end: build locally and deploy public/build, OR build on the server if Node is
# available:
pnpm install && pnpm run build

# Filament's static assets (gitignored) — publish them after deploy:
php artisan filament:assets:publish

# Cache config, routes and views for performance:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point the web server's document root at **`public/`**. Set `APP_ENV=production` and
`APP_DEBUG=false` in `.env`. After changing `.env` or code, re-run the cache commands
(`php artisan optimize` does config + route + view + events in one go;
`php artisan optimize:clear` reverts).

### Database backups

A `mysqldump`-based script is provided (it reads credentials from `.env`, writes a
timestamped gzip to a directory outside the repo, and prunes old dumps):

```bash
BACKUP_DIR=~/backups/aguet ./scripts/backup-db.sh
```

Schedule it with cron, e.g. daily at 03:00:

```cron
0 3 * * *  BACKUP_DIR=$HOME/backups/aguet /path/to/aguet/scripts/backup-db.sh >> $HOME/backups/aguet/backup.log 2>&1
```

## Security notes (public repo)

- `.env` is never committed; `.env.example` carries no secrets. Set real `ADMIN_*` values
  locally / on the server only.
- No credentials are hard-coded anywhere.
- No internal detail of client projects beyond what is on the public site.

## License

Code under the [MIT license](https://opensource.org/licenses/MIT). Site content and the
visual design are © Steve Aguet.
