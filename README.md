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
- [Front-end architecture](#front-end-architecture) — Blade + plain JS modules, the terminal layer
- [Local setup](#local-setup) · [Deployment](#deployment--infomaniak-shared-hosting)
- Companion docs: [visual regression testing](docs/visual-testing.md) ·
  [CI deploy pipeline](DEPLOY.md) ·
  [admin panel internals](docs/reference-admin-panel.md) ·
  [contact message pipeline](docs/explanation-contact-pipeline.md) ·
  [themed error pages](docs/explanation-error-pages.md)

## Stack

- **Laravel 13** · PHP 8.5+
- **Kami** (`psyao/kami`) — the CMS: content types, Locales, routing, page cache, Forms,
  security headers, and its admin as a plugin on the Filament panel
- **Filament 5** — admin panel for editing content
- **MySQL**
- **Tailwind v4** + **Vite** — the terminal aesthetic is authored as custom CSS on top
- **Plain JavaScript modules** — no front-end framework; Alpine and Livewire run in the
  admin panel only
- **JetBrains Mono** — self-hosted at build time via the Vite Bunny font helper
- **pnpm** for front-end tooling

## How it works

### Bilingual

- French is served at `/`, English at `/en`, both by Kami: the Home Page's French and
  English Translations. Kami sets the locale from the URL; the Locales are declared in
  `config/kami.php` (`kami.sites.default.locales`).
- **UI chrome** strings (nav, buttons, labels) live in `lang/fr/site.php` + `lang/en/site.php`.
- **Editorial content** (hero, about, projects, contact) lives in the database, is
  translatable, and is edited in Filament — never in Git.

### Content model

Kami stores the editorial content: each Entry has a Translation per language, French and
English; a Translatable Field holds one value per language, a Shared Field one value for the
whole Entry. The types are declared in `app/ContentTypes` and `app/Taxonomies`, their admin
labels in `lang/<locale>/kami-types.php`.

- **The Home Page** — the one Entry of the `Home` Singleton (`App\ContentTypes\Home`).
  Per language: `hero_title`, `hero_subtitle`, `hero_role`, `hero_location`, `hero_exp`,
  `hero_focus`, `about_body` (rich text), `contact_lead`. Shared: `contact_email`,
  `contact_linkedin`, `contact_linkedin_label`, `contact_github`, `contact_github_label`.
  `hero_title`, `hero_subtitle` and `contact_lead` take inline Markdown (Kami's
  `->inlineMarkdown()`): `*word*` becomes an emphasis the CSS colours green, a newline in the
  title a line break.
- **Projects** — the Entries of the `Project` Content Type (`App\ContentTypes\Project`),
  with no URL of their own, ordered by hand. The title is the project's name; per language:
  `client`, `role`, `summary` (inline Markdown). Shared: `website`, `featured`, `directory`.
  Featured projects render first, full-width. Each Entry is an `App\Models\Project`, whose
  `host()` derives the card's display host from `website` (strips `www.`) and whose
  `legend()` gives the card's `~/projects/<directory>`, else the title as a slug.
- **Skill groups** — the Entries of the `SkillGroup` Content Type (`App\ContentTypes\SkillGroup`),
  the rows of the `tree ~/stack` skills section, with no URL of their own, ordered by hand.
  The title per language, with `text` and `note`. A group renders its Tags, unless `text` is
  set, which renders that sentence instead (e.g. the « Langues » group). `focus`, shared,
  flags the ★ group, highlighted in the tree, whose `note` feeds the tree footer.
- **Tags** — the Terms of the flat `Tag` Taxonomy (`App\Taxonomies\Tag`), listed by both
  types: one name in every language (not translatable), with no URL of their own. An Entry
  keeps its Tags in the order they were picked.

The Home Page, Projects and skill groups render through `Home::render()`.

## Editing the site (Filament admin)

Everything editorial — hero, about, projects, skills, contact — is edited in the Filament
admin panel and stored in the database, never in Git. The panel is in French (it has one
user: the owner) and themed emerald to match the site.

### Sign in

Open **`/admin`** and sign in. The admin user is seeded by `AdminUserSeeder` from the
`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` values in `.env`, with Kami's
`administrator` Role; `php artisan kami:user:grant <email> administrator` gives the Role to
an existing user. Each Entry's edit page works on one language: the Locale menu in its
header switches between the French and English Translations — fill both.

### Home Page

The **Page d’accueil** item opens the Home Page: edit the French texts, switch to English
in the Locale menu for the English ones, and **Preview** before saving. In the title,
`*mot*` renders the word as a green accent and a line break becomes a `<br>`; the about
text is edited as rich text. The email, LinkedIn and GitHub URLs and their displayed labels
(e.g. `/in/steveaguet`) are Shared Fields, entered once for both languages; they feed the
contact section and the ⌘K palette, and the email receives the contact form's messages.

### Projects

The **Projets** item lists the projects: **Classer les enregistrements** sets their order on the page.
Each project has its title (the project's name), `Client`, `Rôle`, `Résumé` (inline
Markdown), per language; its `URL du site`, **Projet phare** (renders first, full-width) and
`Dossier` (the card's `~/projects/…` legend), shared; and its **Tags**. Publish a project's
Translation to show it in that language; a Draft stays off the page.

### Skill groups

The **Compétences** item drives the `tree ~/stack` section, reordered the same way. Each
group has its title, a set of **Tags** and a **Groupe ★ (focus)** toggle. Leave the Tags
empty and fill **Texte** instead to render a sentence rather than Tags (used for the
« Langues » group). The focus group's **Note** shows in the tree footer.

### Tags

The **Tags** item lists the shared vocabulary of projects and skill groups, one name in
every language, so it offers no translation. You rarely open it directly: the Tags input
on a project or skill group searches existing Tags and creates a new one inline, and the
order you pick them in is the order they display.

### Contact messages

The **Messages reçus** item lists the contact form's messages, kept 12 months, with each
one's delivery state; see the [contact message pipeline](docs/explanation-contact-pipeline.md).

## Front-end architecture

The site is server-rendered Blade with a thin layer of plain JavaScript for the terminal
feel. It is built as **progressive enhancement**: every section renders and every link works
with JavaScript disabled — the script only adds the boot animation, the live clock,
click-to-copy, the ⌘K palette, the contact modal's in-place submit, and the status bar's
live vim-mode indicator.

### Request flow

Kami serves the one page: the Home Page at `/`, its English Translation at `/en`, setting
the locale from the URL. `App\ContentTypes\Home::render()` renders the `home` view with the
Entry as `content`, the visible Projects and skill groups in hand order (each with its Tags as
`tags`), and `locale`, `altUrl` and the per-locale `homeUrls`, built from the Locales in
`config('kami.sites.default.locales')` and `$entry->url($locale)` (used by the FR/EN switch
and the `hreflang` tags). `routes/web.php` adds only a few redirects for old addresses before
`Kami::routes()`. Kami's `SecurityHeaders`, appended to the `web` group, sets the CSP: no
inline script or style runs unless its sha256 hash is listed under `kami.security.csp` in
`config/kami.php`, so every behaviour lives in the built bundle.

### Layout and chrome

`layouts/app.blade.php` is the shell: window chrome (traffic-light dots, terminal title, the
⌘K button, the FR/EN switch), the section `tabs`, the `main` content, a tmux-style
`statusbar` (a reactive vim-mode pill that also opens the palette, a branch label with a
deployed-commit popover, a command-line echo, the live clock, locale, year), the
command-palette markup and the contact modal. The current locale's UI strings and the
palette data (projects, contact links) are rendered once in the `#aguet-config` JSON block,
so the script stays locale-agnostic.

### Plain JavaScript modules

No front-end framework: Vite bundles `resources/js/app.js` and the two modules it imports.
Behaviour is wired on `data-*` hooks in the Blade partials (`data-cmdk-open`,
`data-contact-open`, `data-copy`, `data-clock`, …), never on inline handlers, which the CSP
would block.

- **`app.js`** — the one-shot boot intro and the scrollspy that lights the active section
  tab; the status-bar clock (`Europe/Zurich`, refreshed every 15 s); click-to-copy a contact
  value, with an `execCommand` fallback when the clipboard API is unavailable; the status
  bar, whose vim-mode pill reflects the current state (NORMAL / INSERT / VISUAL / COMMAND,
  derived from palette, text-selection and focus) and doubles as the palette trigger, and
  whose branch label opens a popover showing the deployed commit (short SHA → GitHub,
  subject, relative date) read from the generated `config/build_info.php` (written at deploy
  time, see [DEPLOY.md](DEPLOY.md)); the ⌘K command palette, which builds a grouped item list
  (navigation, projects, actions), fuzzy-filters it and supports keyboard navigation — typing
  `:` switches it into a vim-style command line (`:q`, `:wq`, `:help`, `:colorscheme`, …), a
  global `⌘K` / `Ctrl-K` listener toggles it, and a bare `:` opens it straight into command
  mode; a vim-style command-line echo in the status bar; and the contact modal, an accessible
  dialog with its own focus trap.
- **`contact-form.js`** — submits the contact modal's Kami Form with `fetch()` and
  `Accept: application/json`, so the modal shows the errors or the confirmation without a
  reload. Without JavaScript the form posts a full page.
- **`easter-eggs.js`** — keyboard touches for anyone who treats the page like an editor.

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

# 4. Migrate + seed  (schema + FR/EN content + admin user), then Kami's install step
php artisan migrate --seed
php artisan kami:install --no-interaction   # checks, Filament assets, media link, Paths

# 5. Front-end assets
pnpm run build                    # or: pnpm run dev  (HMR during development)

# 6. Serve
php artisan serve                 # http://127.0.0.1:8000  (FR)  ·  /en  (EN)
```

**Shortcuts.** Once the database exists (step 3), `composer setup` runs the
rest of the install in one command (deps, `.env`, key, migrate and seed, `pnpm install` +
build); run `php artisan kami:install --no-interaction` after it, which `composer setup`
leaves out. During
development, `composer dev` runs the queue listener, logs and Vite together.

The admin panel is at **`/admin`**. The admin user is created by `AdminUserSeeder` from the
`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` values in `.env`, with Kami's
`administrator` Role. `php artisan kami:doctor` checks the environment.

## Deployment — Infomaniak shared hosting

Shared hosting with SSH and Composer is enough: no Node, no queue worker, no crontab. A push
to `main` runs the GitHub Actions pipeline described in [DEPLOY.md](DEPLOY.md): CI builds
the front-end assets, rsyncs the release with a fresh `.env`, then runs
[`scripts/deploy.sh`](scripts/deploy.sh) on the server (`composer install`, maintenance
mode, `storage:link`, `kami:install`, `app:import:velox` on the Switch-over release,
`kami:media:warm`, `optimize`, then `up`). The document root points at
**`public/`**, with `APP_ENV=production` and `APP_DEBUG=false`.

Kami's scheduled tasks (contact delivery retries, retention, the sitemap) run in its Tick:
Infomaniak's task planner calls the URL printed by `php artisan kami:tick:url`, whose token
goes in `.env` as `KAMI_TICK_TOKEN`.

### Backups

`scripts/backup.sh` runs on your machine, never on the server. Over the `tsumigeya`
SSH alias (same Infomaniak account), it dumps production's database and copies all
of `storage/app` into `storage/backups/<timestamp>/`, keeping the last ten. Set
`PRODUCTION_PATH` in the local `.env` to the app's folder on the server (the
`DEPLOY_PATH` secret):

```bash
./scripts/backup.sh
```

`scripts/restore.sh` loads the latest snapshot, or the folder it is given, into the
local database and `storage/app`. It refuses to run unless `APP_ENV` is `local`, and
resets every password to `password` with no second factor.

```bash
./scripts/restore.sh
```

## Security notes (public repo)

- `.env` is never committed; `.env.example` carries no secrets. Set real `ADMIN_*` values
  locally / on the server only.
- No credentials are hard-coded anywhere.
- No internal detail of client projects beyond what is on the public site.

## License

Code under the [MIT license](https://opensource.org/licenses/MIT). Site content and the
visual design are © Steve Aguet.
