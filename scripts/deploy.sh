#!/usr/bin/env bash
set -euo pipefail   # exit on error, treat unset vars as errors, catch pipe failures

PHP_BIN="${1:-/opt/php8.5/bin/php}"
COMPOSER_BIN="$(dirname "$PHP_BIN")/composer2.phar"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ "$PHP_BIN" != /* ]]; then
    echo "Error: PHP_BIN must be an absolute path (got: $PHP_BIN)" >&2
    exit 1
fi

cd "$APP_DIR"

# The Switch-over grants the existing User the `administrator` Role by their email,
# read from the .env the deploy job writes, so that the repository holds no address.
ADMIN_EMAIL="$(grep -E '^ADMIN_EMAIL=' .env | tail -n1 | cut -d= -f2- || true)"
ADMIN_EMAIL="${ADMIN_EMAIL%\"}"; ADMIN_EMAIL="${ADMIN_EMAIL#\"}"
if [[ -z "$ADMIN_EMAIL" ]]; then
    echo "Error: ADMIN_EMAIL is empty in .env; the Switch-over's kami:install needs it." >&2
    exit 1
fi

mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p bootstrap/cache

# Install dependencies BEFORE entering maintenance mode to keep downtime minimal.
$PHP_BIN "$COMPOSER_BIN" install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

$PHP_BIN artisan down

# From here on, bring the site back up on exit — success OR failure — so a broken
# step never strands the site in maintenance mode. A failed migration may still
# leave a partially-applied schema to reconcile by hand, but the site is not dark.
# The one exception: once the Velox import has failed, the site stays at 503, so
# that visitors never see a half-imported site (Switch-over release only).
IMPORT_FAILED=0
trap '[[ $IMPORT_FAILED == 1 ]] || $PHP_BIN artisan up || true' EXIT

$PHP_BIN artisan storage:link --force

# Migrates, publishes Kami's assets, checks the content model against the database
# and makes a new Content version, which makes every page-cached page stale.
# Switch-over release only: --email grants the existing User the administrator Role;
# the next release drops it.
$PHP_BIN artisan kami:install --no-interaction --email="$ADMIN_EMAIL"

# Switch-over release only: copies Velox's content into Kami; a no-op once it has
# run. The next release removes this line.
$PHP_BIN artisan app:import:velox || { IMPORT_FAILED=1; echo "Error: app:import:velox failed; the site stays in maintenance." >&2; exit 1; }

$PHP_BIN artisan kami:media:warm
$PHP_BIN artisan optimize   # config, routes, views and events, plus filament:optimize

# The EXIT trap runs `artisan up` here.
