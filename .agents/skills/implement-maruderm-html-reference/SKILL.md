---
name: implement-maruderm-html-reference
description: Convert or update any Maruderm WordPress page, section, or component from the canonical sibling maruderm.html reference while preserving exact markup and class contracts, synchronizing approved CSS, adapting JavaScript to live WordPress/WooCommerce behavior, and validating the integration. Use whenever work is based on maruderm.html or an HTML-agent handoff.
---

# Implement Maruderm HTML Reference

Use `/home/pardus/Hosting/maruderm.html` as the visual source of truth and `/home/pardus/Hosting/maruderm.dev/wp-content/themes/maruderm` as the implementation target.

## Before editing

1. Read the root `AGENTS.md`, `.agents/policy.toml`, and `wp-content/themes/maruderm/docs/reference-assets.md` completely.
2. Inspect the HTML component markup, its CSS, its JavaScript, and `maruderm.html/src/reference-assets.json`.
3. Inspect the current WordPress template/renderer, Vite entry, integration stylesheet, JavaScript entry, enqueue path, and relevant parent-theme overrides.
4. Check git status and diffs in both repositories. Treat HTML changes as reference input; do not edit the HTML repository unless the user explicitly places it in scope.
5. If the change affects catalog routes, cards, badges, filtering, or visibility, also use `.agents/skills/maintain-maruderm-catalog/SKILL.md`.

## Implementation contract

1. Reproduce the canonical semantic hierarchy, class names, `data-*` hooks, control elements, and accessibility attributes in PHP.
2. Replace demo content with live WordPress/WooCommerce data. Preserve escaping, URLs, product identity, stock, price, cart, authentication, nonce, and accessibility behavior.
3. Do not manually recreate or paste canonical styles into WordPress source files.
4. Confirm every required canonical stylesheet is listed in the upstream `src/reference-assets.json` manifest. If it is not, stop and request an updated HTML-agent handoff; do not silently bypass the manifest.
5. Import synchronized styles from `assets/reference/` in the relevant Vite entry. Load them before a small component integration stylesheet.
6. Use integration CSS only for WordPress markup boundaries, WooCommerce selectors, live-content variability, or parent-theme isolation. Never use it to approximate or override the canonical visual design.
7. Do not copy reference demo state, hardcoded product data, or localStorage commerce state.
8. JavaScript is not synchronized. Adapt its interaction contract in WordPress source JavaScript, preserving canonical hooks and visible states while using the platform's real APIs and state.
9. Remove or isolate parent-theme output only at the narrowest registered hook or scoped selector needed for the custom page.
10. Keep rendering, data access, platform behavior, and presentation responsibilities separated according to the existing theme architecture.

## Asset flow

```text
HTML markup + approved CSS manifest
  -> reference-assets.mjs validates and copies CSS
  -> assets/reference checked-in snapshot + SHA-256 lock
  -> WordPress Vite entry imports canonical CSS
  -> integration CSS loads afterward
  -> PHP supplies live content and adapted JavaScript supplies behavior
  -> Vite emits WordPress dist assets
```

`npm run dev` synchronizes before startup, watches the upstream manifest and listed styles, then reloads when they change. `npm run build` synchronizes before producing `dist`. If the sibling repository is absent, only the verified checked-in snapshot may be used.

## Validation

From `wp-content/themes/maruderm`:

1. Run `npm run reference:check`.
2. Run `npm run build`.
3. Run syntax checks for every changed PHP and JavaScript source.
4. Verify the relevant Vite manifest entry exists.
5. Render the real WordPress route and check that canonical structure/classes/hooks are present and legacy parent-theme output is absent.
6. Exercise every changed interaction with live WooCommerce/WordPress state.
7. Visually compare representative desktop and mobile states, including empty, unavailable, error, or authenticated states when applicable.
8. Run `git diff --check`.
9. For catalog-related changes, run `.agents/skills/maintain-maruderm-catalog/scripts/validate-catalog.sh --build`.

## Handoff requirements

Report:

- HTML source component and styles used.
- Synchronized stylesheet targets and Vite consumers.
- WordPress renderer/template, data source, JavaScript adapter, and integration CSS changed.
- Commands and routes validated.
- Any intentional divergence from the canonical HTML contract, with its platform reason.
