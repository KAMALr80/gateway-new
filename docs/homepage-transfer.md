# Homepage import / export

Moves **one storefront client's homepage** (for example `new-england`) from one Gateway to another, typically local → live:

```
export (local) → upload (live) → validate + preview → confirm → backup → import (one transaction) → verify → done
```

Nothing changes on the target until an administrator confirms a preview, and every import can be rolled back.

Ported from the ERP Unity Gateway and adapted to this Gateway's homepage schema (its six section types, its site settings,
and its admin limits). Packages are only exchanged between Gateways with the same homepage schema: a package from a Gateway
with other section types or settings is rejected at validation, never partially applied.

## What is in a package — and what is not

| Included (homepage only) | Never included |
|---|---|
| `homepage_configs`: label, active flag, the homepage keys of `site_settings` (see `HomepageSchema::SITE_SETTING_KEYS`), uploaded site logo and catalog background | users, customers, passwords, tokens, sessions |
| `homepage_sections`: type, title, order, active flag, the allowlisted settings per type | **newsletter subscribers**, orders, carts, addresses, payments |
| `homepage_section_items`: kind, title, alt text, link, order, active flag, images (uploaded file and/or external URL), catalog PDF | product / category / brand **records**, stock, prices |
| homepage-owned uploaded files (images, PDFs), stored by SHA-256 | `.env`, credentials, other storefront settings, full dumps |
| **references** to products, categories and brands by their ERP id (`products.erp_product_id`, `categories.erp_category_id`, `brands.erp_brand_id`) plus the name, for checking | database ids (they differ between environments) |

Settings outside the allowlist are not exported, and on import they are **kept** on the target (never deleted).

The package is a ZIP of JSON and the homepage's own files — not a spreadsheet — so there is no CSV/Excel formula-injection
surface. (The existing newsletter CSV export, `/admin/homepage/{config}/newsletter-subscriptions.csv`, already neutralises
formula prefixes.)

### Section types and settings this Gateway accepts

| Type | Item kinds | Settings | References |
|---|---|---|---|
| `hero` | slide | subtitle, cta_text, cta_url, interval_ms | — |
| `banner` | content | — | — |
| `featured_category` | heading (max 1), content | product_limit | category |
| `product_carousel` | heading (max 1), content (max 2) | limit, badge | products (all required) |
| `brand_showcase` | heading (max 1), brand | — | brands |
| `catalog_showcase` | catalog | eyebrow, heading_phrases, description | — |

### Package layout (schema version 1)

```
homepage-new-england-20261028-120000.zip
├── manifest.json     package_type "homepage", schema_version, export_id, exported_at (UTC), client_key,
│                     content_sha256 (of homepage.json), includes (record counts), warnings
├── homepage.json     the homepage content (deterministic JSON: same homepage ⇒ same bytes)
├── checksums.json    SHA-256 of every other entry
├── README.txt
└── assets/<sha256>.<jpg|png|webp|gif|pdf>
```

Checksums detect damage or edits relative to `checksums.json`; they do **not** prove who made the package. Only accept packages from your own Gateway.

## Permissions

All endpoints sit in the existing admin group: `auth` (session), `verified`, `admin` (`users.is_admin`), CSRF, and a rate limit of
10 requests/minute per administrator (`homepage-transfer`, configurable). Administrators manage every client's homepage here (as on
the Homepage Configs pages); an import can only write the client named in its package. Only the administrator who uploaded a
package can confirm it. There is no public or token-based import endpoint.

## Using it

### Admin UI

**Admin → Homepage → Import / export** (`/admin/homepage-transfer`):

1. **Export**: click the client. A ZIP downloads.
2. **Import** (on the other Gateway): choose the ZIP → *Validate and preview*. You get blocking problems, warnings, reference matches and a change summary.
3. Tick the confirmation and *Confirm import*.
4. **History and backups** lists every validation, import and rollback with its status, checks and a *Roll back* button.

### Command line (export only)

```bash
php artisan homepage:export new-england --output=/path/to/folder
```

Read-only; refuses to overwrite an existing file.

### HTTP API (session + CSRF, JSON)

| Method | Path | Purpose |
|---|---|---|
| GET | `/admin/homepage/{config}/export` | Download the package (`application/zip`) |
| POST | `/admin/homepage-transfer/validate` | multipart `package=@file.zip` → validation + preview. **Read-only for the homepage.** 200 = `validated`, 422 = `invalid` |
| GET | `/admin/homepage-transfer/imports/{id}` | Status of one validation / import / rollback |
| POST | `/admin/homepage-transfer/imports/{id}/confirm` | `{"base_fingerprint": "<from preview>", "confirm_replace": true}` → apply |
| POST | `/admin/homepage-transfer/imports/{id}/rollback` | `{}` or `{"acknowledge_overwrite": true}` → restore the backup taken before that operation |

Responses are `{"data": {...}}` (the import record: `status`, `summary`, `validation`, `verification`, `backup.available`, `can_roll_back`, ...) or
`{"error": {"code": "...", "message": "..."}}`. File paths and package contents are never returned.

## How validation works

Before any content is interpreted: ZIP signature, entry count, an allowlist of entry names (no paths, no `..`, no other files), declared and
**measured** sizes (reads stop at the limit), compression ratio (ZIP bombs), total unpacked size, encrypted entries refused. Then every entry is
checked against `checksums.json`, images are decoded and PDFs checked by content (not by extension), JSON is parsed with a depth limit, the
manifest must match `homepage.json`, unknown fields are rejected, and the content is checked against the same rules as the admin forms (types,
lengths, item kinds and item limits per section, `http(s)` image URLs, app-store links on their own hosts, no `javascript:`/`data:` links,
sections and items numbered in display order).

**References** are resolved by ERP id in the target database. A missing or ambiguous product/category/brand that the homepage uses **blocks**
the import (every product list on this Gateway is displayed). A name that differs between environments is a warning. Nothing is ever created.

The preview compares the package with the target in the same portable form. Sections are matched by type and position (`hero#1`,
`product_carousel#2`), items by kind and position, so an unchanged homepage shows **no changes** and re-importing changes nothing.

## How the import works

`confirm` runs under a per-client lock and:

1. re-validates the stored package (same bytes as previewed) and re-resolves references;
2. refuses if the target homepage changed since the preview (state fingerprint) or the package was already applied;
3. writes a **backup** of the client's homepage rows (with their ids) to the private disk and reads it back to verify it — no backup, no change;
4. in **one database transaction**: updates matched sections/items in place, adds new ones, removes ones not in the package, stores package files
   under `homepage/<client>/<section id>/<sha256>.<ext>`, checks the stored result equals the package, and records success;
5. after the commit, builds the homepage through the public `GET /api/homepage?client=…` code path and checks status, client and section order;
6. dispatches `App\Events\HomepageImported` (hook for any future cache/CDN refresh; nothing listens to it yet).

The homepage config row is updated in place, never deleted and re-created, so its newsletter subscribers are untouched.

If step 4 fails, the transaction rolls back and files written by that attempt are deleted. If step 5 fails, the backup is restored
automatically (`rolled_back`); if that also fails the import is marked `recovery_required`.

**Caching:** the Gateway builds the homepage payload on every request — there is no server-side homepage cache to clear. The storefront
caches the homepage for up to 5 minutes (`revalidate`), so a change can take up to 5 minutes to appear there.

**Idempotency:** a package (export id + content hash) can be applied to a client once; a unique `idempotency_key` enforces this even under races.
To apply the same content again, export a new package or roll back. A failed or rolled-back attempt does not count as applied.

## Backups and rollback

Backups are JSON files in `storage/app/private/homepage-backups/<client>/<uuid>.json` (never web-served); the `homepage_imports` table keeps their
SHA-256. Rollback (from the history) verifies the checksum, refuses if the homepage was edited since the operation (unless acknowledged),
takes a backup of the current state first (so a rollback can itself be rolled back), restores the rows with their original ids in one
transaction, verifies the result and the public API, and dispatches `App\Events\HomepageRestored`. A backup can only be restored into the client
it was taken from.

Rolling back an import that **created** a homepage removes that homepage again — unless newsletter subscribers have joined it since
(deleting the config would cascade to them). That rollback is refused (`restore.subscribers`); deactivate the homepage instead.

Uploaded homepage files are **never deleted** by import or rollback, so old backups stay restorable. Files no longer referenced stay on disk.
The homepage **editor** does delete a file when an image or PDF is replaced or removed; a rollback whose backup still points at such a
file is refused (`rollback.files_missing`) instead of restoring broken images.

## Statuses

`validated` → `in_progress` → `succeeded` | `failed` | `recovery_required` | `rolled_back`; `succeeded` → `rolled_back` | `recovery_required`;
`recovery_required` → `rolled_back`. `invalid`, `failed`, `expired`, `rolled_back` are final. A validation expires after 60 minutes. An operation
`in_progress` for more than 15 minutes is treated as interrupted (an import is marked `failed` — it never committed; a rollback `recovery_required`).

## Deployment prerequisites

1. Deploy the code.
2. Run the one additive migration **when you decide to** (it creates `homepage_imports` only and touches no existing table):
   `php artisan migrate` (`2026_10_28_000001_create_homepage_imports_table`). Imports never run migrations themselves.
   Until it runs, the CLI export works but the admin page and imports do not.
3. PHP upload limits: a package carries the catalog PDFs, so it is often tens of MB (the New England homepage is about 54 MB).
   Set `upload_max_filesize` and `post_max_size` to at least `100M` (the app's own limit, `max_upload_kb`) on the Gateway that imports.
   Also check any web server limit (`client_max_body_size` on nginx).
4. Optional `.env`: `HOMEPAGE_TRANSFER_ENV=local` (label shown in packages; never a URL). Limits are in `config/homepage_transfer.php`.
5. PHP extensions `zip` and `fileinfo`.

## Recovery

- `failed`: nothing changed. Fix the cause and validate again.
- `rolled_back` with category `verification_failed`: the previous homepage was restored automatically; check the history's checks.
- `recovery_required`: the homepage may not match either version. Use **Roll back** on that entry (restores its verified backup), or restore a
  backup file manually via `App\Services\HomepageTransfer\HomepageBackup::restore()` inside a transaction.

## Known limitations

- References need ERP ids that exist in **both** environments. If the local catalog differs from the live one, the import is blocked — by design —
  until the homepage uses products that exist live.
- Section/item identity is positional (type + position): reordering two sections of the same type is shown as updates of both.
- Only the client named in the package can be imported (no import of one client's package into another client).
- Files no longer used remain on disk (by design, for rollback).
- Uploads are processed in the request (no queue). Every entry is read with a hard size limit; catalog PDFs are hashed and copied as
  streams, so memory use stays flat (validating the 54 MB New England package adds no measurable peak memory). The request still
  reads the whole package once, so allow a generous `max_execution_time` on slow hosts.
- A validated package that is never confirmed keeps its upload in `storage/app/private/homepage-imports/` until the next validation
  or confirmation notices it has expired (60 minutes) and removes it.
- Rolling back past an editor change that replaced an image or PDF is refused (see Backups and rollback); re-import a package instead.
