# Maruderm catalog contract

This contract records behavior verified locally on 2026-08-12. It is the primary reference for future catalog work; internet research is unnecessary.

## File map

| Concern | Canonical file |
| --- | --- |
| Route redirects and inherited archive cleanup | `wp-content/themes/maruderm/app/Catalog/CatalogRoutes.php` |
| Product query, category tree, attributes | `wp-content/themes/maruderm/app/Catalog/CatalogRepository.php` |
| Catalog markup and card data | `wp-content/themes/maruderm/app/Catalog/CatalogRenderer.php` |
| WooCommerce archive entry | `wp-content/themes/maruderm/woocommerce/archive-product.php` |
| Filter state and interactions | `wp-content/themes/maruderm/assets/catalog/catalog.js` |
| Catalog Vite entry | `wp-content/themes/maruderm/assets/catalog/index.js` |
| Layout and filter UI | `wp-content/themes/maruderm/assets/globals/components/catalog/catalog.css` |
| Card UI | `wp-content/themes/maruderm/assets/globals/components/catalog/product-card.css` |
| Badge rules | `wp-content/themes/maruderm/app/WooCommerce/ProductBadges.php` and `assets/globals/components/product-badges/` |
| Registration and assets | `app/Bootstrap.php`, `app/Kernel/Enqueue.php`, `vite.config.js`, `dist/manifest.json` |

## Routes and URL state

- Main archive: `/catalog/`.
- Legacy `/shop/`: permanent redirect to `/catalog/` while retaining unrelated query parameters.
- One selected category: its nested pretty permalink, such as `/catalog/parent/child/`. Do not also add `?category=`.
- Two or more selected categories: `/catalog/?category=slug-a,slug-b`.
- Other groups use comma-separated query values:
  - `skin-type`
  - `concern`
  - `hair-need`
  - `price`
- Non-default sorting uses `sort`; search accepts `search` or WordPress `s`.
- Old `/kategoria-tovaru/.../` and a single legacy `/catalog/?category=slug` permanently redirect to the pretty category route.
- While inside the loaded catalog, category/filter changes, chips, clear-all, catalog menu links, breadcrumbs, and Back/Forward use the History API and in-memory cards. They must not reload the page.
- One pretty category route renders the complete in-stock dataset plus visible published products assigned to that category, including descendants. The path supplies initial state, so users can add or remove categories; unavailable cards become hidden when no matching category is selected.

## Product and badge invariants

- Query only published products whose WooCommerce lookup stock status is `instock`.
- Recheck `WC_Product::is_visible()` and `is_in_stock()` after hydration.
- Never let third-party archive query filters reduce the custom catalog dataset.
- Product cards must expose direct and ancestor category slugs so selecting a parent includes descendants.
- Cards expose the data consumed by JavaScript: product id/name, category, skin types, concerns, hair needs, price, popularity, and creation timestamp.
- Out-of-stock products do not appear in the general catalog or homepage merchandising queries.
- A product-category route also renders its assigned unavailable products. Their out-of-stock badge suppresses every promotional/custom badge, and they have no add-to-cart action.
- A homepage/catalog block with no in-stock products must not render an empty product section.

## Filter truth table

- Multiple values inside the same group use OR/union. Example: category A plus category B displays products in A or B.
- Different groups use AND/intersection. Example: `(A or B) and dry-skin and price-range`.
- Search is an additional intersection against the product name.
- A checked option always remains enabled so it can be removed.
- For each unchecked option, clone current state and replace that option's group with only the candidate value. Disable it when no card matches the candidate state. This intentionally ignores active values from the candidate's own group and preserves all other groups.
- Reject a UI interaction if the resulting complete state has zero matches. External catalog category links may clear incompatible secondary filters before applying their single category.
- Disabled options must be visually muted and must not change state when clicked.

## Rendering and CSS invariants

- `[hidden]` must win over `.product-card { display: ... }`; keep an explicit catalog-scoped hidden rule.
- Active-filter typography needs a selector at least as specific as `.maruderm-catalog button`, because the catalog form-control reset uses `font: inherit`.
- Desktop filters are sticky, viewport-height constrained, and independently scrollable. Mobile retains a full-height scrollable drawer and overlay.
- Keep the custom hero, breadcrumbs, filters, toolbar, chips, responsive grid, empty state, wishlist, and add-to-cart UI consistent on shop and product-category archives.
- Do not reintroduce Martfury's archive header or preloader over the custom catalog.

## Required checks

Run:

```bash
.agents/skills/maintain-maruderm-catalog/scripts/validate-catalog.sh --build
```

For client interaction changes, verify in a real browser:

1. `/catalog/` renders the custom root and all in-stock cards.
2. Selecting one category changes to its pretty URL without reload.
3. Selecting another category produces a comma-separated `category` query and unions results.
4. Selecting a value from another group intersects results.
5. An incompatible unchecked value is disabled and cannot create an empty result.
6. Removing a category chip collapses to the remaining pretty route when one category remains.
7. Back/Forward restores inputs, chips, count, hero, sort, and URL without reload.
8. The desktop filter panel scrolls independently while `window.scrollY` stays unchanged.
9. WooCommerce option `woocommerce_coming_soon` has its original value after testing.
