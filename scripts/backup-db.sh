#!/usr/bin/env bash
#
# aguet.dev — MySQL backup via mysqldump.
# Reads DB_* credentials from the project's .env, writes a timestamped gzip dump
# to $BACKUP_DIR (default: ./storage/backups, but use a path OUTSIDE the repo in
# production), and prunes dumps older than $KEEP_DAYS.
#
# Usage:   BACKUP_DIR=~/backups/aguet ./scripts/backup-db.sh
# Cron:    0 3 * * * BACKUP_DIR=$HOME/backups/aguet /path/to/aguet/scripts/backup-db.sh
#
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$PROJECT_DIR/.env"
BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/storage/backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"

[ -f "$ENV_FILE" ] || { echo "No .env at $ENV_FILE" >&2; exit 1; }

# Read a key from .env, stripping surrounding quotes.
env_get() {
  local v
  v="$(grep -E "^$1=" "$ENV_FILE" | head -n1 | cut -d= -f2-)"
  v="${v%\"}"; v="${v#\"}"; v="${v%\'}"; v="${v#\'}"
  printf '%s' "$v"
}

DB_HOST="$(env_get DB_HOST)";     DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)";     DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"

[ -n "$DB_DATABASE" ] || { echo "DB_DATABASE is empty in .env" >&2; exit 1; }

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$BACKUP_DIR/${DB_DATABASE}-${STAMP}.sql.gz"

echo "Backing up '$DB_DATABASE' -> $OUT"
MYSQL_PWD="$DB_PASSWORD" mysqldump \
  --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" \
  --single-transaction --quick --no-tablespaces \
  "$DB_DATABASE" | gzip > "$OUT"

# Prune old dumps.
find "$BACKUP_DIR" -name "${DB_DATABASE}-*.sql.gz" -type f -mtime "+${KEEP_DAYS}" -delete

echo "Done. Current backups:"
ls -1t "$BACKUP_DIR"/"${DB_DATABASE}"-*.sql.gz 2>/dev/null | head
