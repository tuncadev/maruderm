#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
THEME_ROOT="${PROJECT_ROOT}/wp-content/themes/maruderm"
BUILD_ASSETS=false

if [[ "${1:-}" == "--build" ]]; then
    BUILD_ASSETS=true
elif [[ $# -gt 0 ]]; then
    printf 'Usage: %s [--build]\n' "$0" >&2
    exit 2
fi

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        printf 'Missing required command: %s\n' "$1" >&2
        exit 1
    fi
}

require_pattern() {
    local pattern="$1"
    local file="$2"
    local message="$3"

    if ! rg -Fq -- "$pattern" "$file"; then
        printf 'Contract check failed: %s (%s)\n' "$message" "$file" >&2
        exit 1
    fi
}

for command_name in php node npm rg git wp; do
    require_command "$command_name"
done

printf 'Validating PHP syntax...\n'
while IFS= read -r php_file; do
    php -l "$php_file" >/dev/null
done < <(find "${THEME_ROOT}/app/Catalog" -type f -name '*.php' -print | sort)
php -l "${THEME_ROOT}/app/WooCommerce/ProductBadges.php" >/dev/null
php -l "${THEME_ROOT}/woocommerce/archive-product.php" >/dev/null

printf 'Validating JavaScript syntax...\n'
node --check "${THEME_ROOT}/assets/catalog/catalog.js"

printf 'Checking catalog behavior contracts...\n'
require_pattern "lookup.stock_status = 'instock'" "${THEME_ROOT}/app/Catalog/CatalogRepository.php" 'repository must query in-stock products'
require_pattern '&& $product->is_in_stock()' "${THEME_ROOT}/app/Catalog/CatalogRepository.php" 'repository must recheck hydrated product stock'
require_pattern 'productsForCurrentView' "${THEME_ROOT}/app/Catalog/CatalogRepository.php" 'category routes must add their assigned unavailable products'
require_pattern 'data-in-stock=' "${THEME_ROOT}/app/WooCommerce/ProductCardRenderer.php" 'product cards must expose stock context'
require_pattern 'if ($product->is_in_stock())' "${THEME_ROOT}/app/WooCommerce/ProductCardRenderer.php" 'unavailable cards must suppress cart actions'
require_pattern 'matchesStockContext' "${THEME_ROOT}/assets/catalog/catalog.js" 'unavailable cards must remain category-scoped'
require_pattern "category: 'category'" "${THEME_ROOT}/assets/catalog/catalog.js" 'category query-state mapping is missing'
require_pattern "selected.join(',')" "${THEME_ROOT}/assets/catalog/catalog.js" 'multi-value URL serialization is missing'
require_pattern 'matchesAny(values(card' "${THEME_ROOT}/assets/catalog/catalog.js" 'within-group union matching is missing'
require_pattern 'const updateAvailability' "${THEME_ROOT}/assets/catalog/catalog.js" 'no-empty option gating is missing'
require_pattern "window.history[mode === 'push' ? 'pushState' : 'replaceState']" "${THEME_ROOT}/assets/catalog/catalog.js" 'History API synchronization is missing'
require_pattern '.maruderm-catalog [hidden]' "${THEME_ROOT}/assets/globals/components/catalog/catalog.css" 'hidden cards need a catalog-scoped display override'
require_pattern 'overflow-y: auto' "${THEME_ROOT}/assets/globals/components/catalog/catalog.css" 'filter panel must scroll independently'

printf 'Checking WooCommerce data invariants...\n'
coming_soon_before="$(wp --path="${PROJECT_ROOT}" --skip-plugins --skip-themes option get woocommerce_coming_soon 2>/dev/null || true)"
wp --path="${PROJECT_ROOT}" eval '
$repository = new \Maruderm\Catalog\CatalogRepository();
$products = $repository->products();
$unavailable = array_filter(
    $products,
    static fn ($product): bool => !($product instanceof \WC_Product) || !$product->is_in_stock()
);

if ($products === []) {
    WP_CLI::error("The custom catalog repository returned no products.");
}

if ($unavailable !== []) {
    WP_CLI::error("The custom catalog repository returned unavailable products.");
}

WP_CLI::log(sprintf("Catalog repository: %d in-stock products, 0 unavailable products.", count($products)));
'
coming_soon_after="$(wp --path="${PROJECT_ROOT}" --skip-plugins --skip-themes option get woocommerce_coming_soon 2>/dev/null || true)"

if [[ "$coming_soon_before" != "$coming_soon_after" ]]; then
    printf 'Validation changed woocommerce_coming_soon unexpectedly.\n' >&2
    exit 1
fi

if [[ "$BUILD_ASSETS" == true ]]; then
    printf 'Building Vite assets...\n'
    npm --prefix "$THEME_ROOT" run build
fi

printf 'Checking generated catalog manifest entries...\n'
node --input-type=module -e '
import fs from "node:fs";
const manifest = JSON.parse(fs.readFileSync(process.argv[1], "utf8"));
for (const entry of ["assets/catalog/index.js", "assets/globals/index.js"]) {
  if (!manifest[entry]?.file) {
    throw new Error(`Missing Vite manifest entry: ${entry}`);
  }
}
' "${THEME_ROOT}/dist/manifest.json"

printf 'Checking whitespace errors...\n'
git -C "$PROJECT_ROOT" diff --check

printf 'Catalog validation passed. Coming-soon mode remains: %s\n' "${coming_soon_after:-unset}"
