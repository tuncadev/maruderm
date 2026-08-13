# Maruderm WordPress Agent Instructions

Load the global rules from `/home/pardus/.codex/AGENTS.md` first. These rules are the project-local authority for `/home/pardus/Hosting/maruderm.dev`.

## HTML Reference Implementations

- `/home/pardus/Hosting/maruderm.html` is the canonical visual reference repository.
- For every WordPress page, section, or component derived from that repository, use `.agents/skills/implement-maruderm-html-reference/SKILL.md` before editing.
- Preserve the canonical HTML hierarchy, class names, `data-*` attributes, accessibility attributes, and JavaScript hook elements exactly unless WordPress or WooCommerce requires a documented adapter.
- Do not recreate, approximate, rename, or manually copy canonical CSS. Only CSS explicitly listed in `maruderm.html/src/reference-assets.json` may enter the theme through the reference synchronization system.
- Do not edit `wp-content/themes/maruderm/assets/reference/` directly. It is a generated, checked-in snapshot.
- Import each synchronized stylesheet from the relevant Vite entry before the component's WordPress-only integration stylesheet.
- Keep integration CSS small and limited to CMS, WooCommerce, or parent-theme compatibility. It must not restyle or duplicate the canonical design.
- HTML-repository JavaScript is not synchronized. Reimplement or adapt its interaction contract in the appropriate WordPress source entry, preserving hooks and visible behavior while using real WordPress/WooCommerce state.
- Never synchronize static demo product data, localStorage commerce state, hardcoded URLs, or reference-only content into WordPress. Render live CMS and WooCommerce data with escaping, nonce, authentication, stock, cart, and accessibility behavior intact.
- `npm run dev` and `npm run build` synchronize approved CSS automatically. `npm run reference:check` must pass before handoff.
- When adding a new canonical stylesheet, the HTML agent must add it to the upstream manifest and the WordPress agent must import it from a theme Vite entry. The reference check fails for unconsumed synchronized styles.
- If the sibling HTML repository is unavailable, builds may use only the verified checked-in snapshot and SHA-256 lock; do not bypass failed integrity checks.

## Catalog Work

- For `/catalog/`, product-category routes, filters, product cards, badges, or catalog visibility, also use `.agents/skills/maintain-maruderm-catalog/SKILL.md` and run its validator.

## Completion

- Change source files, never generated assets by hand; rebuild Vite outputs after source changes.
- Run proportional PHP, JavaScript, build, reference-contract, and browser/runtime checks.
- Append the required activity and progress logs.
- Leave successful task-owned changes uncommitted unless the user explicitly requests a commit.
