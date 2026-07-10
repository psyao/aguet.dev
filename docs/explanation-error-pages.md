# Explanation: why the error pages are self-contained

`resources/views/errors/{403,404,419,429,500,503}.blade.php` render a themed error
page matching the site's terminal aesthetic. Each is a one-liner
(`@include('errors._shell', ['code' => 'NNN'])`) — all the real logic lives in
`errors/_shell.blade.php`. Two decisions there look unusual until you consider *when*
an error page actually has to render.

## The problem

A normal Blade view can assume a lot: the asset build succeeded, the database is
reachable, the request matched a route and ran through the app's middleware stack. An
error page is the one place that assumption is wrong by definition — a 500 might mean
the database just went down; a 503 fires *because* the app is in maintenance mode,
mid-deploy, with no guarantee the current asset manifest matches the code that's
running. If the error page itself depends on any of that, you get an error rendering
an error — the worst possible experience for whoever hit the failure.

404/403/419/429 look safer (they don't imply the app is broken), but they share a
subtler problem: they don't reliably run through the same middleware pipeline a normal
page does.

## The approach

### Zero build dependency, zero framework services

`_shell.blade.php` has no `@vite` directive, no Livewire component, no JS. All CSS is
inlined in a `<style>` block, hand-copied from a subset of `app.css`'s default-theme
tokens (`--bg`, `--fg`, `--accent`, etc. — see the comment at the top of the file).
This means the page renders identically whether the Vite manifest exists, whether
`composer install` finished, or whether the site is mid-deploy under `artisan down`
(the 503 case `scripts/deploy.sh` triggers is the exact moment this page is *most*
likely to be seen).

### Locale resolved independently of `SetLocale`

`SetLocale` (the middleware that normally sets the app locale from the URL) is
attached only to the routes in `routes/web.php` — the matched `/` and `/{locale}`
routes. A request that doesn't match any route (the definition of a 404) never enters
that middleware group at all, so relying on it here would render every 404 in the
fallback locale regardless of what the visitor typed. `_shell.blade.php` re-derives
the locale itself, straight from `request()->segment(1)` against the same
`config('aguet.locales')` / `default_locale` values `SetLocale` uses — duplicated
logic, not shared, because the whole point is that it must work in the cases where
the shared code path didn't run.

### Title and message derive from the error code alone

Each view file passes only `code` to the shared shell; `_shell.blade.php` looks up
`site.errors.{code}.title` and `site.errors.{code}.message` from the `lang/{locale}/site.php`
files itself. Adding a new themed error page for another HTTP status is then a
two-line change (a new one-liner view file + two new translation keys), not a copy of
the whole shell.

## Trade-offs

- **Inline CSS duplicates `app.css` instead of importing it.** A future palette
  change to `app.css` has to be manually mirrored into `_shell.blade.php`'s `<style>`
  block, or the two will drift. Accepted because the alternative — depending on the
  Vite-built stylesheet — is exactly the dependency this page exists to avoid.
- **Locale detection logic is duplicated, not shared, with `SetLocale`.** Two places
  now need to agree on how a locale is derived from a URL segment. Accepted for the
  same reason: sharing code would mean sharing the code path, and the code path is
  what's unavailable when this page needs to render.
- **Known gap:** the 419 (expired session / CSRF mismatch) page assumes the session
  store itself is reachable enough to have expired a token in the first place. What
  happens when the session store is *unreachable* — not just expired — is unverified;
  tracked as an open item in [TODOS.md](../TODOS.md).

## Related

- Test coverage: `tests/Feature/ErrorPagesTest.php`.
- [README: front-end architecture](../README.md) for the theme tokens this page's
  inline CSS is a subset of.
