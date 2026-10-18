# Moving aguet.dev onto Velox

The friction log of moving this Site onto [Velox](https://github.com/psyao/velox), tracked by Velox's map "Map: aguet.dev on Velox, in production" (psyao/velox#309). Each friction is numbered in the order found: the step (the map's ticket), what happened, the workaround, and the verdict. A friction that is a Velox gap also gets a ticket in Velox.

## Steps

| Step | Ticket | Outcome |
| --- | --- | --- |
| 1 | Update aguet's PHP and JS dependencies to Velox's floors (psyao/velox#310) | Laravel 13.19 → 13.37, Filament 5.7.0-beta2 → 5.10.2 (stable, `minimum-stability` back to `stable`), Pest 4 → 5, PHPUnit 12 → 13, Guzzle 7 → 8 (through Laravel), Boost 2.4 → 2.10 and its skills reinstalled; Vite 8.3, Tailwind 4.3.3, laravel-vite-plugin 3.2; Node 26 in `.nvmrc` and `.node-version` (CI reads the latter). 149 tests pass; the visual suite fails as on `main` (friction 1) |
| 2 | Regenerate aguet's visual baselines on today's macOS and Chromium (psyao/velox#319) | Playwright 1.61.1 → 1.64.1 (Chromium v1248); the 9 failing baselines regenerated on macOS 26.6 after eyeballing every diff: text anti-aliasing only, plus the EN mobile contact modal no longer captured with the viewport scrolled 11px sideways. 11 of 13 pass, 2 skipped (print), stable over two runs |

## Friction log

| # | Step | What happened | Workaround | Verdict |
| --- | --- | --- | --- | --- |
| 1 | 1 | The visual suite fails 9 of 13 snapshots (home FR/EN desktop and mobile, contact modal ×4, command palette), on `main` as on the update: its baselines were taken on macOS 15.7.5 with Chromium v1223/v1228, this machine now runs macOS 26.6 with Chromium v1243 | None: the failures predate the update | Closed in step 2: baselines regenerated on macOS 26.6 with Chromium v1248 |
| 2 | 1 | Playwright 1.64 needs Chromium v1248, not installed here; a newer Chromium also re-renders the snapshots | Playwright kept at 1.61.1 | Closed in step 2: Playwright 1.64.1 and its Chromium installed |
