#!/usr/bin/env bash
set -euo pipefail   # exit on error, treat unset vars as errors, catch pipe failures

# Replays aguet.dev's public URL inventory (#28 decision 6) against a base URL,
# e.g. `scripts/replay-urls.sh http://aguet-rehearsal.test` on a rehearsal of the
# Switch-over, or `scripts/replay-urls.sh https://aguet.dev` right after its `up`.
# Each line: path, expected status and expected Location relative to the base
# (`-` for none), both extended globs. Redirects are not followed. Exits 1 if
# any URL differs.
#
# Velox's social image answers 200 while its file is still served from
# public/storage, then the 301 to Kami's Conversion once the Velox cleanup
# (#28 decision 7) deletes it. Herd's nginx answers 404 for robots.txt whatever
# the file: replay it against Apache.

shopt -s extglob

BASE="${1:?Usage: $0 <base-url>}"
BASE="${BASE%/}"

INVENTORY='
/                                                               200 -
/en                                                             200 -
/en/                                                            301 /en
/EN                                                             301 /en
/sitemap.xml                                                    200 -
/robots.txt                                                     200 -
/up                                                             200 -
/une-page-absente                                               404 -
/admin/login                                                    200 -
/storage/media/01m5pgcpaafgk30py2pce8c412/aguet-dev-1200.webp   @(200|301) @(-|/media/*)
'

failed=0
while read -r path status location; do
    [[ -z "$path" ]] && continue

    read -r got_status got_location < <(curl -sS -o /dev/null --max-redirs 0 -w '%{http_code} %{redirect_url}\n' "$BASE$path")
    got_location="${got_location#"$BASE"}"
    got_location="${got_location:--}"

    # Unquoted on the right: globs.
    # shellcheck disable=SC2053
    if [[ "$got_status" == $status && "$got_location" == $location ]]; then
        echo "ok    $status $path"
    else
        echo "FAIL  $path: expected $status $location, got $got_status $got_location"
        failed=1
    fi
done <<< "$INVENTORY"

exit "$failed"
