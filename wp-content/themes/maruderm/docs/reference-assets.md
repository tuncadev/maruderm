# HTML reference asset synchronization

The sibling `maruderm.html` repository is the visual source of truth. Its `src/reference-assets.json` manifest explicitly lists presentation-only CSS that may be synchronized into this WordPress theme.

This is an explicit contract, not whole-repository mirroring. HTML/PHP, JavaScript, images, demo data, and localStorage state are not copied automatically.

Run `npm run reference:sync` to update the checked-in snapshot under `assets/reference/`. `npm run reference:check` fails when the snapshot or its SHA-256 lock is stale.

Run `npm run reference:status` before every HTML-agent handoff. It reads `scripts/reference-implementations.json`, reports which approved targets are fully implemented versus merely synchronized and pending, and shows the current HTML commit plus dirty source files mapped to implementation IDs. Unmapped dirty source files are explicitly reported for manual intake. For a requested page or component, `npm run reference:status -- --require <implementation-id-or-css-target>` is the completion gate.

Both `npm run build` and `npm run dev` synchronize before Vite starts. While the development server is running, changes to the upstream manifest or any listed source are synchronized and trigger a browser reload.

Every implemented manifest-listed stylesheet must be imported from `assets/reference/` by at least one WordPress JavaScript/Vite entry. Approved styles whose canonical PHP structure does not exist yet must instead be declared with a reason in `scripts/reference-assets.consumers.json`. Synchronization fails when a non-pending stylesheet has no consumer, when a pending target is absent from the HTML manifest, or when a pending stylesheet gains a consumer without being removed from the pending list.

The manifest and consumer configuration serve different purposes: `maruderm.html/src/reference-assets.json` approves files for copying and hashing, while `scripts/reference-assets.consumers.json` records which approved files are intentionally waiting for a matching WordPress implementation. Pending files are still synchronized into the checked-in snapshot and lock, but they are not bundled or loaded through dummy imports.

`scripts/reference-implementations.json` is the end-to-end ownership registry. It must cover every manifest target and map its HTML markup and JavaScript to the current WordPress state, renderers, Vite entries, live routes, legacy consumers, and blocker when pending. The status validator checks the registry against real source imports and both manifest/pending configurations.

Pending means “available for a future implementation,” not “live change complete.” If a user requests a pending page/component or a shared change that should affect its live legacy equivalent, the WordPress agent must implement the canonical structure and behavior, remove the pending record, register the real consumers, and prove the actual route. It must not report success after copying CSS alone.

Current implementation status:

- All manifest-approved targets have registered WordPress consumers and renderers.
- The homepage removes the inherited Martfury content container before rendering the canonical full-width sections.
- The login implementation includes both the dedicated WooCommerce authentication page and the global logged-out slide-in drawer.
- Runtime verification remains mandatory because the registry validates ownership and consumers, not visual or behavioral parity by itself.

The sibling repository defaults to `../../../../maruderm.html` relative to the theme. Set `MARUDERM_REFERENCE_ROOT` to an alternate checkout path when necessary.

If the HTML repository is unavailable in CI or production, synchronization verifies and uses the checked-in snapshot and SHA-256 lock instead. A missing source therefore does not make a previously synchronized build non-reproducible; a missing or altered snapshot does fail the build.

Files under `assets/reference/` are generated snapshots. Do not edit them directly. Platform-specific selectors belong in component integration styles loaded after the synchronized assets. Static product data, cart state, and notification behavior are never synchronized into WordPress.

## Component implementation flow

1. The HTML agent completes the canonical semantic markup, class names, hook attributes, interaction contract, and CSS.
2. The WordPress agent audits the upstream task, commit/dirty diff, all relevant markup/CSS/JavaScript/assets, and the current implementation registry.
3. The HTML agent exposes every approved canonical stylesheet through `src/reference-assets.json`.
4. The WordPress agent preserves the markup, classes, `data-*` hooks, controls, and accessibility attributes while replacing demo content with live CMS/WooCommerce data.
5. The WordPress agent imports the synchronized target from the relevant Vite entry before a small WordPress-only integration stylesheet.
6. The WordPress agent adapts the HTML JavaScript behavior in theme source JavaScript. JavaScript is not synchronized automatically.
7. The WordPress agent updates the implementation registry and removes completed targets from the pending list.
8. `npm run reference:check` verifies source/snapshot equality, the lock, registry coverage, and real consumers; `npm run reference:status -- --require ...` proves requested scope is implemented.
9. `npm run build` synchronizes and emits the WordPress assets.
10. The real route is checked for expected bundles, exact structure/hooks, live behavior, absence of superseded legacy output, and desktop/mobile visual fidelity.

For shared palette, typography, token, foundation, or behavior changes, also scan active legacy WordPress sources and generated bundles. A canonical value can be updated and synchronized correctly while an older live renderer still loads unrelated legacy CSS; this is an implementation gap, not a successful live sync.

The authoritative agent workflow is `.agents/skills/implement-maruderm-html-reference/SKILL.md`, and strict project rules are in the repository-root `AGENTS.md` and `.agents/policy.toml`.
