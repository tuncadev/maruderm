# Maruderm WordPress Agent Instructions

Load the global rules from `/home/pardus/.codex/AGENTS.md` first. These rules are the project-local authority for `/home/pardus/Hosting/maruderm.dev`.

## HTML Reference Implementations

- `/home/pardus/Hosting/maruderm.html` is the canonical visual reference repository.
- For every WordPress page, section, or component derived from that repository, use `.agents/skills/implement-maruderm-html-reference/SKILL.md` before editing.
- Start every HTML handoff by running `npm --prefix wp-content/themes/maruderm run reference:status`, then inspect the HTML repository's current status, latest task/progress entries, relevant diff, markup, CSS, JavaScript, and assets. The HTML working tree may contain the newest approved source before commit; record whether input is committed or uncommitted.
- Preserve the canonical HTML hierarchy, class names, `data-*` attributes, accessibility attributes, and JavaScript hook elements exactly unless WordPress or WooCommerce requires a documented adapter.
- Do not recreate, approximate, rename, or manually copy canonical CSS. Only CSS explicitly listed in `maruderm.html/src/reference-assets.json` may enter the theme through the reference synchronization system.
- Do not edit `wp-content/themes/maruderm/assets/reference/` directly. It is a generated, checked-in snapshot.
- Import each synchronized stylesheet from the relevant Vite entry before the component's WordPress-only integration stylesheet.
- Keep integration CSS small and limited to CMS, WooCommerce, or parent-theme compatibility. It must not restyle or duplicate the canonical design.
- HTML-repository JavaScript is not synchronized. Reimplement or adapt its interaction contract in the appropriate WordPress source entry, preserving hooks and visible behavior while using real WordPress/WooCommerce state.
- Never synchronize static demo product data, localStorage commerce state, hardcoded URLs, or reference-only content into WordPress. Render live CMS and WooCommerce data with escaping, nonce, authentication, stock, cart, and accessibility behavior intact.
- `npm run dev` and `npm run build` synchronize approved CSS automatically. `npm run reference:check` must pass before handoff.
- When adding a new canonical stylesheet, the HTML agent must add it to the upstream manifest. The WordPress agent must either import it from the matching theme Vite entry or declare it with a concrete implementation reason in `scripts/reference-assets.consumers.json`; pending styles must not be loaded through dummy imports, and the reference check fails if a pending style gains a consumer without being removed from that list.
- `scripts/reference-implementations.json` is the authoritative map from approved HTML markup/CSS/JavaScript to WordPress renderers, Vite entries, live routes, legacy consumers, and completion status. Update it whenever a component is implemented, replaced, renamed, or gains a consumer.
- A synchronized snapshot or a `pending` entry is not a completed WordPress implementation. If the user's requested page/component is pending, implement its canonical PHP structure and adapted JavaScript, remove it from the pending list, register the real consumers, and run `npm run reference:status -- --require <id-or-css-target>`. Do not report the requested live change as synced while that command fails.
- For shared tokens, palette, typography, foundation, component API, or behavior changes, audit every active WordPress implementation and every registered legacy consumer. A successful file hash does not prove that a live route uses the changed asset.
- Completion requires proof from the real WordPress route: expected canonical selectors/hooks, correct generated bundle and cascade order, adapted interaction behavior, absence of superseded legacy output, and representative desktop/mobile states.
- If the sibling HTML repository is unavailable, builds may use only the verified checked-in snapshot and SHA-256 lock; do not bypass failed integrity checks.

## Catalog Work

- For `/catalog/`, product-category routes, filters, product cards, badges, or catalog visibility, also use `.agents/skills/maintain-maruderm-catalog/SKILL.md` and run its validator.

## KeyCRM Product Names

- Strict rule: every KeyCRM product name must be English.
- The canonical English-name source is `products/product-pricing-table.xlsx`, matched by the unique `Barcode` value and read from `Title English`.
- Routine WooCommerce-to-KeyCRM reconciliation must match products by unique SKU/barcode and must never send or copy the WooCommerce product name into KeyCRM.
- A KeyCRM `name` write is allowed only for an explicit English-name correction or initial creation using a verified English value from the canonical workbook. If the English value is missing or the SKU/barcode match is not unique, stop or skip instead of using the WooCommerce title.

## Image Optimization

- For product or other project raster-image resizing, format conversion, or metadata reduction, use `.agents/skills/optimize-project-images/SKILL.md`.
- Run its dry-run mode before mutating a real image folder. Runtime backups and manifests belong under `arch/backups/` and must not be deleted automatically.

## Daily Production Database Sync

- Before the first substantive Maruderm task on each Europe/Kyiv calendar day, use `.agents/skills/sync-maruderm-production-db/SKILL.md` and run its default daily sync command.
- The successful state marker at `.agents/state/daily-production-db-sync.json` is authoritative for whether that Kyiv day has already been synchronized. A successful same-day run is a no-op; do not use `--force` unless the user explicitly requests another same-day refresh or the saved state is invalid.
- The daily gate must finish successfully before continuing with the day's first task. If production SSH, export, local backup, import, URL migration, or validation fails, report the blocked gate and preserve the rollback/source artifacts; do not treat a failed attempt as completed.
- This workflow is production-to-local database only. It must not deploy code, upload media, mutate the production database, or run a local-to-production synchronization.

## Local Database to Production

- When the user explicitly requests replacing the production database from local, use `.agents/skills/sync-maruderm-local-db-to-production/SKILL.md`.
- Always run its read-only preflight before `--execute`; never bypass commerce-divergence checks, verified source/rollback exports, maintenance mode, serialization-safe URL migration, automatic rollback, or post-import validation.
- This workflow changes only the production database. It must not deploy code or media, and it must retain the downloaded production rollback backup locally.

## Completion

- Change source files, never generated assets by hand; rebuild Vite outputs after source changes.
- Run proportional PHP, JavaScript, build, reference-contract, and browser/runtime checks.
- Append the required activity and progress logs.
- Leave successful task-owned changes uncommitted unless the user explicitly requests a commit.
