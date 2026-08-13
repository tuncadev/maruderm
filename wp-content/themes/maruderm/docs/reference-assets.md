# HTML reference asset synchronization

The sibling `maruderm.html` repository is the visual source of truth. Its `src/reference-assets.json` manifest explicitly lists presentation-only CSS that may be synchronized into this WordPress theme.

This is an explicit contract, not whole-repository mirroring. HTML/PHP, JavaScript, images, demo data, and localStorage state are not copied automatically.

Run `npm run reference:sync` to update the checked-in snapshot under `assets/reference/`. `npm run reference:check` fails when the snapshot or its SHA-256 lock is stale.

Both `npm run build` and `npm run dev` synchronize before Vite starts. While the development server is running, changes to the upstream manifest or any listed source are synchronized and trigger a browser reload.

Every manifest-listed stylesheet must be imported from `assets/reference/` by at least one WordPress JavaScript/Vite entry. Synchronization fails when an approved stylesheet has no consumer, so adding a new source requires both an HTML manifest entry and a WordPress entrypoint import.

The sibling repository defaults to `../../../../maruderm.html` relative to the theme. Set `MARUDERM_REFERENCE_ROOT` to an alternate checkout path when necessary.

If the HTML repository is unavailable in CI or production, synchronization verifies and uses the checked-in snapshot and SHA-256 lock instead. A missing source therefore does not make a previously synchronized build non-reproducible; a missing or altered snapshot does fail the build.

Files under `assets/reference/` are generated snapshots. Do not edit them directly. Platform-specific selectors belong in component integration styles loaded after the synchronized assets. Static product data, cart state, and notification behavior are never synchronized into WordPress.

## Component implementation flow

1. The HTML agent completes the canonical semantic markup, class names, hook attributes, interaction contract, and CSS.
2. The HTML agent exposes every approved canonical stylesheet through `src/reference-assets.json`.
3. The WordPress agent preserves the markup, classes, `data-*` hooks, controls, and accessibility attributes while replacing demo content with live CMS/WooCommerce data.
4. The WordPress agent imports the synchronized target from the relevant Vite entry before a small WordPress-only integration stylesheet.
5. The WordPress agent adapts the HTML JavaScript behavior in theme source JavaScript. JavaScript is not synchronized automatically.
6. `npm run reference:check` verifies source/snapshot equality, the SHA-256 lock, and that every synchronized stylesheet has a Vite consumer.
7. `npm run build` synchronizes and emits the WordPress assets.
8. The real route is checked for structure, live behavior, parent-theme leakage, and desktop/mobile visual fidelity.

The authoritative agent workflow is `.agents/skills/implement-maruderm-html-reference/SKILL.md`, and strict project rules are in the repository-root `AGENTS.md` and `.agents/policy.toml`.
