# aguet.dev

Personal one-page portfolio for **Steve Aguet**, a full-stack (back-end-leaning) Laravel
developer in the Lake Geneva region. Bilingual (FR default, EN), self-editable through a
Filament admin panel. The site itself is meant as a demonstration of craft — the code is
public.

The visual direction is **"Terminal v2"**: a dark terminal/IDE aesthetic, monospace
throughout, with a typed boot intro, a ⌘K command palette, a tmux-style status bar, and a
clean light theme when printed to PDF.

## Stack

- **Laravel 13** · PHP 8.4+
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

- **`SiteContent`** — an editorial singleton (`SiteContent::current()`): hero fields,
  about (Markdown), contact lead. `hero_title` uses a small convention: `**word**` renders
  as an accent emphasis and a newline as a line break.
- **`Project`** — the projects collection: translatable `name/client/role/summary`, plus
  `slug`, `stack[]`, `url`, `featured`, `sort_order`, `is_published`. Featured projects
  render full-width.
- Skills are static content in `config/skills.php` (titles translated via the lang files).

## Local setup

Requirements: PHP 8.4+, Composer, Node 20+, pnpm, a running MySQL server.

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
