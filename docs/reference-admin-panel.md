# Reference: Filament admin panel internals

The public site's editorial content is managed at `/admin`, a single Filament 5 panel.
This doc covers the panel's internal structure — resources, pages, widgets — for
anyone extending it. For how to *use* the panel day to day, see the
[README's admin walkthrough](../README.md#editing-the-site-filament-admin).

## Panel configuration

Defined in `app/Providers/Filament/AdminPanelProvider.php`:

- **id/path**: `admin`, mounted at `/admin`.
- **Auth**: `->login()` — Filament's built-in login page, no registration. Any
  authenticated `User` can access the panel (`User::canAccessPanel()` always returns
  `true` — this is a single-owner app, not multi-tenant).
- **Brand**: "aguet.dev", primary color `Color::Emerald`.
- **Plugin**: `TranslatableFieldsPlugin::make()->supportedLocales(['fr' => 'Français', 'en' => 'English'])`
  from `outerweb/filament-translatable-fields` — renders FR/EN tabs on any form field
  marked `.translatable()`.
- **Navigation groups**: `Contenu`, `Taxonomies`, `Système` — every resource/page
  declares which group it belongs to.
- **Discovery**: resources auto-discovered from `app/Filament/Resources`, pages from
  `app/Filament/Pages`, widgets from `app/Filament/Widgets`. `Dashboard`,
  `AccountWidget`, and `FilamentInfoWidget` are registered explicitly on top of
  discovery (Filament's stock dashboard page and its two default widgets).

## Resources

Filament 5's file layout splits each resource into a `Resource.php` plus
`Pages/`, `Schemas/`, and `Tables/` subfolders (no monolithic `getForm()`/`getTable()`
static methods on the resource class itself).

| Resource | Nav group | CRUD | Notes |
|---|---|---|---|
| `ContactMessageResource` | — | Read-only | `canCreate()` returns `false`, no edit page — only `Pages/ListContactMessages`. Uses an **infolist** (`Schemas/ContactMessageInfolist`) instead of a form, since messages aren't editable. Table: `Tables/ContactMessagesTable`. `getNavigationBadge()` returns the unread count. |

Projects, skill groups and Tags have no Resource of aguet's own: Velox's generic Entry
Resources serve the `Project` and `SkillGroup` Content Types (Projets, Compétences, ordered
by drag) and its Term Resource the `Tag` Taxonomy.

## Pages (non-resource)

- The Home Page's texts are no longer a page of the panel: they are the Home Page's
  Entry, edited in Velox's **Accueil** Entry Resource (`App\ContentTypes\Home`).
- **`DatabaseBackup`** (nav group Système, "Sauvegarde DB") — two header actions:
  `download` streams a SQL dump via `App\Support\DatabaseDumper`; `restore` (danger
  color, confirmation required, 50MB `FileUpload` limit) rehydrates via
  `App\Support\DatabaseRestorer`. Both classes live in `app/Support/` and are injected
  into the page's actions rather than called statically.

## Widgets

Both are auto-discovered (not explicitly registered — see Discovery above).

- **`StatsOverview`** (`StatsOverviewWidget`) — three stats: unread message count
  (`ContactMessage::whereNull('read_at')->count()`), published vs. draft project
  counts, and featured project count, each Project counted once, on its Origin.
- **`LatestMessages`** (`TableWidget`) — unread `ContactMessage` rows, newest first,
  not paginated. Each row has a "Voir" action linking to
  `ContactMessageResource::getUrl('index')`. Empty state when there are no unread
  messages.

## Related

- [README: admin panel walkthrough](../README.md#editing-the-site-filament-admin) — how-to,
  end-user perspective.
- [Explanation: contact message pipeline](explanation-contact-pipeline.md) — the
  `ContactMessageResource`/`StatsOverview`/`LatestMessages` widgets all read from the
  same `contact_messages` table this doc describes.
