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

# The private-package token (COMPOSER_AUTH JSON, for psyao/velox), piped on stdin by the
# deploy job: never stored on the shared host, never in its process list, and given to
# Composer alone. Run by hand from a terminal, nothing is read.
COMPOSER_AUTH_JSON=""
if [[ ! -t 0 ]]; then
    IFS= read -r COMPOSER_AUTH_JSON || true
fi

mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p bootstrap/cache

# Install dependencies BEFORE entering maintenance mode to keep downtime minimal.
COMPOSER_AUTH="$COMPOSER_AUTH_JSON" $PHP_BIN "$COMPOSER_BIN" install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

$PHP_BIN artisan down

# From here on, always bring the site back up on exit — success OR failure — so a
# broken step (e.g. a failed migration) never strands the site in maintenance mode.
# Note: a failed migration may still leave a partially-applied schema to reconcile
# manually, but the site itself will not be left dark.
trap '$PHP_BIN artisan up || true' EXIT

$PHP_BIN artisan storage:link --force
$PHP_BIN artisan migrate --force
$PHP_BIN artisan velox:fields:check   # fails the deploy on stored content the code can't read
$PHP_BIN artisan optimize   # config, routes, views and events, plus filament:optimize
$PHP_BIN artisan queue:restart

# The EXIT trap runs `artisan up` here.
