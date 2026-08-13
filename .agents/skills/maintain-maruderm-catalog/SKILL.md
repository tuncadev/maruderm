---
name: maintain-maruderm-catalog
description: Maintain and validate the custom Maruderm WooCommerce catalog, including /catalog/ and product-category routes, in-stock product queries, product cards and badges, multi-select facets, URL/history synchronization, no-empty-results controls, responsive filter styling, and generated Vite assets. Use for any Maruderm catalog page, category archive, catalog filter, product-card visibility, catalog badge, catalog route, or filter-panel UI change.
---

# Maintain Maruderm Catalog

Preserve the verified catalog contract without researching it again. Work only in `/home/pardus/Hosting/maruderm.dev` and use the existing theme architecture.

## Workflow

1. When catalog work derives from `/home/pardus/Hosting/maruderm.html`, first use `../implement-maruderm-html-reference/SKILL.md`, then apply this catalog-specific contract.
2. Read `references/catalog-contract.md` completely before changing catalog behavior.
3. Inspect the current contents and diff of every target file. Preserve unrelated dirty work.
4. Keep responsibilities separated:
   - `CatalogRoutes` owns canonical redirects and removal of inherited archive UI.
   - `CatalogRepository` owns in-stock products and taxonomy metadata.
   - `CatalogRenderer` owns server-rendered catalog markup and product data attributes.
   - `assets/catalog/catalog.js` owns client filtering, sorting, History API state, chips, availability, and the mobile drawer.
   - component CSS owns presentation; `assets/catalog/index.js` remains the Vite entry.
5. Implement the smallest change that preserves every invariant in the reference.
6. Run `scripts/validate-catalog.sh`. Add `--build` when source CSS/JS or Vite entries changed.
7. For interaction changes, additionally test one category, two categories, one cross-group filter, a disabled zero-result option, chip removal, and browser Back. Confirm the page instance does not reload.
8. If WooCommerce coming-soon mode is temporarily changed for a browser test, capture its original value and restore it with a trap before reporting completion.
9. Follow project activity logging and progress-log requirements. Leave changes uncommitted unless the user explicitly requests a commit.

## Guardrails

- Do not search the internet for this workflow; the local reference contains the established behavior.
- Do not restore WooCommerce's default archive loop, sidebar, category header, or pagination.
- Do not fetch after each filter change while every in-stock card is already present in the document.
- Do not exclude unrelated category options on a category route; the route is initial filter state, not a reduced catalog dataset.
- Do not change union/intersection semantics or permit a selectable zero-result combination.
- Keep the general `/catalog/` dataset and merchandising queries in-stock only. On a product-category route, include unavailable products assigned to that category, show only their out-of-stock badge, suppress their cart action, and hide them again when no matching category is selected.
- Do not edit generated files directly. Change source assets and rebuild.
- Treat `.maruderm-catalog button, input, select { font: inherit; }` and component `display` declarations as cascade hazards; use catalog-scoped selectors with adequate specificity.

## Resources

- Read `references/catalog-contract.md` for the route matrix, filter truth table, file map, invariants, and validation cases.
- Run `scripts/validate-catalog.sh --build` for deterministic PHP, JavaScript, source-contract, WooCommerce-data, asset-manifest, build, and whitespace checks.
