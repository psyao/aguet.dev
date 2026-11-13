---
name: kami-development
description: Builds, installs, deploys and checks sites on Kami, the CMS package in this Laravel application. Use when creating a Kami site or adding Kami to an application, declaring a Content Type or its Fields, writing or publishing Entries, storing Media, adding a Locale, rendering a type, reacting to content changes, changing security headers, managing Roles and Permissions, deploying or updating a site, fixing a kami:doctor or kami:install problem, or choosing what runs on shared hosting.
---

# Kami development

Each task has one guide, the source of truth: open it before acting, and follow it over anything remembered.

| Task | Guide |
| --- | --- |
| Create a new site from the starter kit | `vendor/psyao/kami/docs/guide/installation.md#creating-a-site` |
| Add Kami to an existing application or to its Filament panel | `vendor/psyao/kami/docs/guide/existing-application.md` |
| Declare a Content Type or its Fields, tabs, sections and conditions, with its labels, its view and its migration | `vendor/psyao/kami/docs/guide/content-types.md` |
| Start a type (`kami:make:type Name`, `--singleton`, `--global-set`, `--taxonomy`) | `vendor/psyao/kami/docs/guide/content-types.md#declaring-a-type` |
| Declare a type with no URL (`$routable = false`) or a Singleton (`Home::entry()`) | `vendor/psyao/kami/docs/guide/content-types.md#types-with-no-url` |
| Let editors order a type by hand (`$orderable = true`, `Entry::query()->ordered()`) | `vendor/psyao/kami/docs/guide/content-types.md#types-ordered-by-hand` |
| Put a type's Entries in a tree (`$hierarchical = true`, `parent` on `SaveEntry`, `Term::ancestors()`) | `vendor/psyao/kami/docs/guide/content-types.md#hierarchical-types` |
| Hold values shown across the Site in a Global Set (`Footer::entry()`), or read `SiteSettings` | `vendor/psyao/kami/docs/guide/content-types.md#global-sets` |
| Link Entries with `Entries`, or classify them with a Taxonomy and `Terms` | `vendor/psyao/kami/docs/guide/content-types.md#relations` |
| Hold repeated or grouped values in one Field (`Repeater`, `Group`) | `vendor/psyao/kami/docs/guide/content-types.md#repeaters-and-groups` |
| Change a declared model: rename or drop a Field or type, flip `$translatable` (`ContentMigrator`), or fix content model drift | `vendor/psyao/kami/docs/guide/content-types.md#schema-changes` |
| Replace a type's admin form or table (`EntryForm`, `EntryTable`), or add a Field type or an admin page | `vendor/psyao/kami/docs/guide/content-types.md#in-the-admin` |
| Seed content from code | `vendor/psyao/kami/docs/guide/content-types.md#writing-entries` |
| Write, publish, schedule or unpublish Entries, from the admin or from code | `vendor/psyao/kami/docs/guide/publishing.md` |
| Duplicate an Entry (`DuplicateEntry`) | `vendor/psyao/kami/docs/guide/content-types.md#duplicating-an-entry` |
| Trash or restore Entries (`TrashEntry`, `RestoreEntry`) | `vendor/psyao/kami/docs/guide/content-types.md#the-trash` |
| Act on selected rows, or add a row, header or bulk action of your own writing through a core action | `vendor/psyao/kami/docs/guide/content-types.md#your-own-actions` |
| React to content changes with Content events | `vendor/psyao/kami/docs/guide/publishing.md#reacting-to-content` |
| Store or delete an image or a document (`SaveMedia`, `DeleteMedia`), write its alt text, change what the library accepts, or pre-generate and prune Conversions (`kami:media:warm`, `kami:media:prune`) | `vendor/psyao/kami/docs/guide/media.md` |
| Add a Locale, or understand Translations, Fallback and `lang` | `vendor/psyao/kami/docs/guide/multilingual.md` |
| Render a type, write its view, override `render()`, place `Kami::routes()` or localise error pages (`Kami::exceptions()`) | `vendor/psyao/kami/docs/guide/routing-and-rendering.md` |
| Cache a fragment (`Kami::remember()`), keep a page out of the page cache (`Kami::withoutPageCache()`), make it stale after a change Kami cannot see (`Kami::flushCache()`, `kami:cache:clear`), serve it before Laravel boots (the `public/index.php` line) or turn it off | `vendor/psyao/kami/docs/guide/page-cache.md` |
| Give pages their head (`<x-kami::seo />`, `Kami::seo()`), write a Meta title, description, Social image or Hidden from search engines, change a type's `summary()`, `socialImage()` or `$ogType`, keep a Site out of search engines or open it to them (`KAMI_INDEXABLE`), or own or fix `robots.txt` | `vendor/psyao/kami/docs/guide/seo.md` |
| Load from another origin, extend the front CSP, or weigh an option that weakens it | `vendor/psyao/kami/docs/guide/security.md` |
| Give the User model Kami's Roles, declare a Role, or check a Permission | `vendor/psyao/kami/docs/guide/users-and-roles.md` |
| Read what the Audit log records, record a site's own event (`RecordAuditEvent`), group a long run into one event (`AuditContext::batch()`), or change its retention (`kami:audit:prune`) | `vendor/psyao/kami/docs/guide/audit-log.md` |
| Create the first administrator or give an existing User the Role, from a terminal or a deploy script | `vendor/psyao/kami/docs/guide/installation.md#the-first-user` |
| Fix a problem `kami:doctor` or `kami:install` reports | The section of `vendor/psyao/kami/docs/guide/installation.md` the problem names |
| Fix a Path clash, or Paths that do not follow the declarations | `vendor/psyao/kami/docs/guide/routing-and-rendering.md#after-a-deploy` |
| Deploy a site for the first time | `vendor/psyao/kami/docs/guide/deployment.md#first-deploy` |
| Deploy again, update Kami, or ship a changed Prefix or Locale | `vendor/psyao/kami/docs/guide/deployment.md#later-deploys` |
| Measure a page's cached and uncached server time against the budgets (`kami:bench`) | `vendor/psyao/kami/docs/guide/deployment.md#measuring-page-speed` |
| Run Kami's Scheduled tasks and the site's own (`Kami::schedule()`, `kami:tick`, a server's crontab, the planner URL `kami:tick:url`, visits) | `vendor/psyao/kami/docs/guide/scheduler.md` |
| Let visitors write to the site and mail it to the owner (`kami:make:form`, `kami.forms`, a `Form`'s `rules()`, `recipients()` and `notification()`, `<x-kami::form>`, `<x-kami::form.errors>`, `Kami::form()->sent()`, `StoreSubmission`, `SubmissionStored`, `SubmissionReceived`, `kami:forms:deliver`) | `vendor/psyao/kami/docs/guide/forms.md` |
| Decide whether something can run on the host (cron, queue, mail over SMTP, Node, PHP limits) | `vendor/psyao/kami/docs/guide/hosting.md` |
| Understand why Kami works the way it does | `vendor/psyao/kami/docs/adr/` |

A task the table does not cover is outside what Kami offers today: say so, and keep to Kami's public classes rather than its `Internal` namespaces under `vendor/psyao/kami/src/`.
