# Visual regression testing

Pest 4 browser tests capture pixel baselines of the current design so the
CSS → Tailwind conversion can be verified pixel-for-pixel.

## Run

    composer test:visual            # builds assets, runs the browser suite

The default unit/feature suite is unaffected and still uses in-memory SQLite:

    php artisan test

## What is captured

Home FR/EN (desktop + mobile) and the ⌘K command palette open state. All run
against `/?screenshot=1`, a testing-only mode (`resources/views/layouts/app.blade.php`)
that:

- skips the boot/typing intro and disables all animations/transitions,
- freezes the live clock (`00:00`) and year,
- re-asserts **JetBrains Mono** with a higher-specificity `!important` rule so it
  wins over the `font-family: Arial !important` that `assertScreenshotMatches()`
  injects for cross-machine portability,
- signals `document.fonts.ready` so self-hosted fonts are loaded before capture.

Baselines live in `tests/.pest/snapshots/` (committed). `tests/Browser/Screenshots/`
holds only debug screenshots + image-diff views and is gitignored.

The browser suite runs against a **file-backed** SQLite DB
(`database/testing.sqlite`, gitignored), migrated + seeded before each test by the
`Browser` `beforeEach` in `tests/Pest.php`. Content therefore reflects the
**seeders**, not locally hand-edited rows.

## Regenerate a baseline (after an INTENTIONAL visual change)

    ./vendor/bin/pest -c phpunit.browser.xml --update-snapshots

This rewrites the snapshots. Review the new images in `git diff`, then commit.
Add `--diff` (without `--update-snapshots`) to open a visual diff of what moved.
Never update baselines to "make it pass" without eyeballing the change.

A baseline is a base64-encoded PNG. To view one:

    base64 -d -i tests/.pest/snapshots/Browser/HomeVisualTest/<name>.snap -o /tmp/x.png && open /tmp/x.png

## Print

`pest-plugin-browser` 4.x has **no** print-media emulation, so print is NOT
captured automatically (see the skipped test in
`tests/Browser/InteractiveVisualTest.php`). Validate it manually once per cycle:
open the home page, print to PDF, and check pagination, `@page` margins, and the
light print theme — none of which a screenshot could assert anyway.

## Pinned environment (baselines are environment-specific)

Baselines were generated on:

- OS: macOS 15.7.5 (Darwin 24.6.0)
- Playwright: 1.60.0
- Chromium: Chrome for Testing 148.0.7778.96 (playwright chromium v1223)

A different browser build re-renders text/anti-aliasing differently and will fail
the diffs. The suite runs in one fixed local environment (not CI). Regenerate
baselines if you change any of the above.
