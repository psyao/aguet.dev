# Reference: Filament admin panel internals

The public site's editorial content is managed at `/admin`, a single Filament 5 panel.
This doc covers the panel's internal structure — resources, pages, widgets — for
anyone extending it. For how to *use* the panel day to day, see the
[README's admin walkthrough](../README.md#editing-the-site-filament-admin).

## Panel configuration

Defined in `app/Providers/Filament/AdminPanelProvider.php`:

- **id/path**: `admin`, mounted at `/admin`.
- **Auth**: `->login()` and `->passwordReset()` — Filament's built-in pages, no
  registration. A `User` can access the panel once it holds a Kami Role
  (`User::canAccessPanel()` checks `kamiRoles()`); access inside the panel goes
  through Kami's Permissions.
- **Brand**: "aguet.dev", primary color `Color::Emerald`.
- **Plugin**: `Psyao\Kami\Admin\KamiPlugin::make()` — Kami's whole admin: the screens
  of every declared type (Entries, Terms, the Singleton page), Media, the Audit log and
  the Submissions screen, among others.
- **Avatars**: `defaultAvatarProvider(InitialsAvatarProvider::class)` draws initials
  on the server instead of sending names to ui-avatars.com.
- **Security headers**: `'kami.security-headers:admin'` ends the panel's middleware
  (Kami's admin CSP, with the nonce Filament's inline scripts carry).
- **Discovery**: resources from `app/Filament/Resources`, pages from
  `app/Filament/Pages`, widgets from `app/Filament/Widgets`. Only the last holds
  anything today. `Dashboard` (Filament's stock page) is registered explicitly.

## Resources

Filament 5's file layout splits each resource into a `Resource.php` plus
`Pages/`, `Schemas/`, and `Tables/` subfolders (no monolithic `getForm()`/`getTable()`
static methods on the resource class itself).

aguet declares no Resource of its own. The contact Form's messages are Kami's
Submissions, listed on Kami's **Messages reçus** screen, which counts the unread ones.
The panel runs `->strictAuthorization()`: a Resource added later needs its policy.

Projects, skill groups and Tags have no Resource of aguet's own: Kami's generic Entry
Resources serve the `Project` and `SkillGroup` Content Types (Projets, Compétences, ordered
by drag) and its Term Resource the `Tag` Taxonomy.

## Pages (non-resource)

- The Home Page's texts are no longer a page of the panel: they are the Home Page's
  Entry, edited on Kami's **Page d’accueil** Singleton page (`App\ContentTypes\Home`).

## Widgets

Auto-discovered (not explicitly registered — see Discovery above).

- **`StatsOverview`** (`StatsOverviewWidget`) — published vs. draft project counts,
  and featured project count, each Project counted once, on its Origin.

## Related

- [README: admin panel walkthrough](../README.md#editing-the-site-filament-admin) — how-to,
  end-user perspective.
