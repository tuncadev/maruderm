---
name: implement-maruderm-html-reference
description: Convert, update, or synchronize any Maruderm WordPress page, section, component, shared style/token, asset, or interaction from the canonical sibling maruderm.html repository while preserving exact markup contracts, consuming approved CSS, adapting JavaScript to live WordPress/WooCommerce behavior, removing legacy consumers, and proving the requested route is implemented. Use for every HTML-agent handoff or request to get, pull, sync, match, or apply changes from maruderm.html.
---

# Implement Maruderm HTML Reference

Use `/home/pardus/Hosting/maruderm.html` as the canonical visual and interaction source and `/home/pardus/Hosting/maruderm.dev/wp-content/themes/maruderm` as the platform implementation.

## Intake

1. Read the root `AGENTS.md`, `.agents/policy.toml`, and `wp-content/themes/maruderm/docs/reference-assets.md` completely.
2. From the theme, run `npm run reference:status` before editing.
3. Inspect the HTML repository's `git status`, recent log, latest relevant `.agents/progress.md` and activity events, and the relevant diff. Record whether the handoff source is committed or uncommitted.
4. Read all relevant HTML page/component markup, canonical CSS, JavaScript, assets, and `src/reference-assets.json`. Do not infer behavior from CSS alone.
5. Inspect the mapped WordPress status, renderers, Vite entries, routes, and legacy consumers in `scripts/reference-implementations.json`.
6. Inspect current WordPress diffs before editing and preserve other agents' work. Do not edit the HTML repository unless the user explicitly puts it in scope.
7. Also use `maintain-maruderm-catalog` for catalog routes, filters, cards, badges, or visibility.

## Classify the handoff

- Markup changes require matching PHP hierarchy, elements, classes, `data-*` hooks, ARIA, and control semantics.
- CSS changes require a manifest-approved synchronized target and a real Vite consumer for implemented scope.
- JavaScript changes require a WordPress adapter preserving the visible state machine and hooks while using platform APIs.
- Asset changes require local theme ownership and correct enqueue/build handling; never hotlink the reference repository.
- Demo data/state changes are design examples only. Use live WordPress/WooCommerce data, authentication, nonces, stock, cart, URLs, and persistence.
- Shared token, typography, palette, foundation, or component API changes require auditing every active and registered legacy WordPress consumer. Hash equality alone is insufficient.

## Implement

1. Reproduce canonical semantic structure and hook attributes exactly in the appropriate renderer/template.
2. Replace only demo content/state with live platform data and behavior. Preserve escaping, identity, stock, price, cart, authentication, nonces, and accessibility.
3. Synchronize canonical CSS through the manifest. Never recreate it, paste it into integration CSS, rename selectors, or edit `assets/reference/` directly.
4. Import synchronized CSS in the matching Vite entry before a narrowly scoped WordPress/WooCommerce/parent-theme adapter.
5. Adapt canonical JavaScript in WordPress source; do not synchronize static localStorage commerce state or hardcoded data.
6. Remove superseded legacy output at the narrowest hook/template boundary. Do not leave old and canonical implementations competing on the live route.
7. Update `scripts/reference-implementations.json` with the real status, reference files, WordPress renderers, entries, routes, and legacy consumers.
8. Remove an implemented target from `scripts/reference-assets.consumers.json`. Pending is allowed only for work outside the user's requested scope and requires a concrete blocker.

## Completion gate

A copied snapshot, passing hash, rebuilt bundle, or pending record does not mean a requested live change is complete.

For every requested component/page, run:

```bash
npm run reference:status -- --require <implementation-id-or-css-target>
```

Do not report success while this fails. If it is pending, implement the matching PHP and behavior rather than adding a dummy import. The status command validates that the manifest, pending configuration, implementation registry, real Vite imports, reference files, renderers, and routes agree.

## Runtime validation

1. Run `npm run reference:check` and `npm run build`.
2. Run syntax checks for changed PHP and JavaScript.
3. Confirm the real route loads the expected generated entry and reference CSS before integration CSS.
4. Confirm canonical selectors, hooks, controls, and accessibility attributes appear in rendered HTML.
5. Exercise changed behavior against live WordPress/WooCommerce state, including applicable loading, empty, error, unavailable, and authenticated states.
6. Confirm superseded legacy markup/styles/output are absent.
7. Compare representative desktop and mobile states with the HTML reference.
8. For shared style changes, scan source and generated bundles for stale legacy declarations and explain any intentional remaining occurrences.
9. Run `git diff --check`; run the catalog validator when applicable.

## Handoff report

Report the upstream task/commit or dirty diff, requested implementation IDs, reference markup/CSS/JS used, WordPress renderers/entries/routes changed, real route and interaction checks, any out-of-scope pending targets, validation commands, and justified divergences. Never summarize a requested pending target as synchronized or complete.
