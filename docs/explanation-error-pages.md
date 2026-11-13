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
likely to be seen). Kami's CSP blocks inline styles, so the `<style>` block passes by
its sha256 hash under `style-src` in `config/kami.php`: any edit to it means a new
hash there.

### Locale resolved for every error, not by the page

Kami sets the app locale from the URL when it serves one of its pages. An error
raised elsewhere (a 500 deep in a request, a 404 on a path no page holds, a route of
aguet's own) renders outside that, so the error page would fall back to the default
locale regardless of what the visitor typed. `Kami::exceptions($exceptions)`, first
in `bootstrap/app.php`'s `withExceptions()`, closes that gap: before Laravel renders
any `errors/{code}` view, it sets the locale from the URL's first segment against the
Site's Locales, so an error under `/en/…` renders in English. `_shell.blade.php` itself
only reads `app()->getLocale()`. The one exception: an error raised while the
application is still booting (a broken config file, say) renders in the default
locale, since reading the Site then could fail in turn.

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
- **The locale depends on Kami's exception callback.** If `Kami::exceptions()` were
  dropped from `bootstrap/app.php`, every error page outside Kami's own pages would
  render in French. `tests/Feature/ErrorPagesTest.php` covers the English case.
- **Known gap:** the 419 (expired session / CSRF mismatch) page assumes the session
  store itself is reachable enough to have expired a token in the first place. What
  happens when the session store is *unreachable* — not just expired — is unverified;
  tracked as an open item in [TODOS.md](../TODOS.md).

## Related

- Test coverage: `tests/Feature/ErrorPagesTest.php`.
- [README: front-end architecture](../README.md) for the theme tokens this page's
  inline CSS is a subset of.
