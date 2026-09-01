<?php
/**
 * Exposes the maruderm.dev catalog (products + category/attribute/price
 * filter options) through WPGraphQL for the headless Next.js frontend.
 *
 * Reuses Maruderm\Catalog\CatalogRepository's public methods so the
 * headless filter option lists/counts match the live PHP-rendered catalog.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', 'maruderm_register_catalog_graphql');

function maruderm_register_catalog_graphql(): void
{
    register_graphql_object_type('MarudermProductBadge', [
        'fields' => [
            'tone' => ['type' => 'String'],
            'label' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermCatalogProduct', [
        'fields' => [
            'databaseId' => ['type' => 'Int'],
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'imageUrl' => ['type' => 'String'],
            'imageAlt' => ['type' => 'String'],
            'priceHtml' => ['type' => 'String'],
            'price' => ['type' => 'Float'],
            'categoryLabel' => ['type' => 'String'],
            'categorySlugs' => ['type' => ['list_of' => 'String']],
            'skinTypeSlugs' => ['type' => ['list_of' => 'String']],
            'concernSlugs' => ['type' => ['list_of' => 'String']],
            'hairNeedSlugs' => ['type' => ['list_of' => 'String']],
            'popularity' => ['type' => 'Int'],
            'createdTimestamp' => ['type' => 'Int'],
            'inStock' => ['type' => 'Boolean'],
            'purchasable' => ['type' => 'Boolean'],
            'badge' => ['type' => 'MarudermProductBadge'],
        ],
    ]);

    register_graphql_object_type('MarudermCatalogFilterOption', [
        'fields' => [
            'value' => ['type' => 'String'],
            'canonicalValue' => ['type' => 'String'],
            'label' => ['type' => 'String'],
            'count' => ['type' => 'Int'],
            'depth' => ['type' => 'Int'],
            'url' => ['type' => 'String'],
            'description' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermCatalogNavCategory', [
        'fields' => [
            'value' => ['type' => 'String'],
            'canonicalValue' => ['type' => 'String'],
            'label' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'imageUrl' => ['type' => 'String'],
            'tone' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermCatalog', [
        'fields' => [
            'products' => ['type' => ['list_of' => 'MarudermCatalogProduct']],
            'categoryOptions' => ['type' => ['list_of' => 'MarudermCatalogFilterOption']],
            'skinTypeOptions' => ['type' => ['list_of' => 'MarudermCatalogFilterOption']],
            'concernOptions' => ['type' => ['list_of' => 'MarudermCatalogFilterOption']],
            'hairNeedOptions' => ['type' => ['list_of' => 'MarudermCatalogFilterOption']],
            'navigationCategories' => ['type' => ['list_of' => 'MarudermCatalogNavCategory']],
        ],
    ]);

    register_graphql_field('RootQuery', 'marudermCatalog', [
        'type' => 'MarudermCatalog',
        'args' => [
            'language' => ['type' => 'String'],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_catalog(
            (string) ($args['language'] ?? 'uk')
        ),
    ]);

    register_graphql_field('RootQuery', 'marudermProductsByIds', [
        'type' => ['list_of' => 'MarudermCatalogProduct'],
        'args' => [
            'ids' => ['type' => ['non_null' => ['list_of' => 'Int']]],
            'language' => ['type' => 'String'],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_products_by_ids(
            $args['ids'],
            (string) ($args['language'] ?? 'uk')
        ),
    ]);

    register_graphql_field('RootQuery', 'marudermProductSearch', [
        'type' => ['list_of' => 'MarudermCatalogProduct'],
        'args' => [
            'term' => ['type' => ['non_null' => 'String']],
            'limit' => ['type' => 'Int'],
            'language' => ['type' => 'String'],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_product_search(
            (string) $args['term'],
            isset($args['limit']) ? (int) $args['limit'] : 6,
            (string) ($args['language'] ?? 'uk')
        ),
    ]);
}

/** @return array<int, array<string, mixed>> */
function maruderm_resolve_product_search(string $term, int $limit, string $language = 'uk'): array
{
    $term = trim($term);

    if (mb_strlen($term) < 3) {
        return [];
    }

    $repository = new \Maruderm\Catalog\CatalogRepository();
    $matches = [];

    foreach (maruderm_headless_catalog_products() as $product) {
        $mappedProduct = maruderm_map_catalog_product($repository, $product, $language);

        if (mb_stripos($mappedProduct['name'], $term) !== false) {
            $matches[] = $mappedProduct;

            if (count($matches) >= $limit) {
                break;
            }
        }
    }

    return $matches;
}

function maruderm_resolve_catalog(string $language = 'uk'): array
{
    $repository = new \Maruderm\Catalog\CatalogRepository();
    $taxonomyResolver = new \Maruderm\Multilingual\TaxonomyPresentationResolver();
    $products = maruderm_headless_catalog_products();

    return [
        'products' => array_map(
            static fn (\WC_Product $product): array => maruderm_map_catalog_product(
                $repository,
                $product,
                $language
            ),
            $products
        ),
        'categoryOptions' => $taxonomyResolver->localizeOptions(
            $repository->categoryOptions($products),
            'product_cat',
            $language
        ),
        'skinTypeOptions' => $taxonomyResolver->localizeOptions(
            $repository->attributeOptions($products, 'pa_skin_type'),
            'pa_skin_type',
            $language
        ),
        'concernOptions' => $taxonomyResolver->localizeOptions(
            $repository->attributeOptions($products, 'pa_skin_problem'),
            'pa_skin_problem',
            $language
        ),
        'hairNeedOptions' => $taxonomyResolver->localizeOptions(
            $repository->attributeOptions($products, 'pa_hair_need'),
            'pa_hair_need',
            $language
        ),
        'navigationCategories' => array_map(
            static fn (array $category): array => [
                'value' => $category['value'],
                'canonicalValue' => $category['canonicalValue'] ?? $category['value'],
                'label' => $category['label'],
                'url' => $category['url'],
                'imageUrl' => $category['image'],
                'tone' => $category['tone'],
            ],
            $taxonomyResolver->localizeNavigation($repository->navigationCategories(), $language)
        ),
    ];
}

/** @return \WC_Product[] */
function maruderm_headless_catalog_products(): array
{
    $products = wc_get_products([
        'status' => 'publish',
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    return array_values(array_filter(
        $products,
        static fn ($product): bool => $product instanceof \WC_Product
            && $product->get_catalog_visibility() !== 'hidden'
    ));
}

function maruderm_map_catalog_product(
    \Maruderm\Catalog\CatalogRepository $repository,
    \WC_Product $product,
    string $language = 'uk'
): array
{
    $identityResolver = new \Maruderm\Multilingual\ProductIdentityResolver();
    $taxonomyResolver = new \Maruderm\Multilingual\TaxonomyPresentationResolver();
    $presentation = $identityResolver->presentationPost($product->get_id(), $language);
    $imageId = $product->get_image_id();
    $imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'woocommerce_thumbnail') : false;
    $topCategories = $repository->topCategories($product);
    $categoryLabel = $topCategories[0]->name ?? 'Maruderm';
    if (isset($topCategories[0]) && $topCategories[0] instanceof \WP_Term) {
        $categoryLabel = $taxonomyResolver->translateTerm($topCategories[0], $language)->name;
    }
    $createdAt = $product->get_date_created();
    $translator = new \Maruderm\Multilingual\ProductDetailTranslator();
    $badge = maruderm_resolve_product_badge($product);
    if (is_array($badge)) {
        $badge['label'] = $translator->text((string) $badge['label'], $language);
    }

    return [
        'databaseId' => $product->get_id(),
        'name' => $presentation instanceof \WP_Post ? $presentation->post_title : $product->get_name(),
        'slug' => $presentation instanceof \WP_Post ? $presentation->post_name : $product->get_slug(),
        'url' => $presentation instanceof \WP_Post && $language === 'ru'
            ? home_url('/ru/tovar/' . $presentation->post_name . '/')
            : $product->get_permalink(),
        'imageUrl' => is_string($imageUrl) ? $imageUrl : wc_placeholder_img_src('woocommerce_thumbnail'),
        'imageAlt' => wp_strip_all_tags(
            $presentation instanceof \WP_Post ? $presentation->post_title : $product->get_name()
        ),
        'priceHtml' => $translator->html($product->get_price_html(), $language),
        'price' => $product->is_in_stock() ? (float) $product->get_price() : null,
        'categoryLabel' => $categoryLabel,
        'categorySlugs' => $taxonomyResolver->productTermSlugs($product, 'product_cat', $language),
        'skinTypeSlugs' => $taxonomyResolver->productTermSlugs($product, 'pa_skin_type', $language),
        'concernSlugs' => $taxonomyResolver->productTermSlugs($product, 'pa_skin_problem', $language),
        'hairNeedSlugs' => $taxonomyResolver->productTermSlugs($product, 'pa_hair_need', $language),
        'popularity' => $product->get_total_sales(),
        'createdTimestamp' => $createdAt !== null ? $createdAt->getTimestamp() : 0,
        'inStock' => $product->is_in_stock(),
        'purchasable' => $product->is_purchasable(),
        'badge' => $badge,
    ];
}

/** @return array{tone: string, label: string}|null */
function maruderm_resolve_product_badge(\WC_Product $product): ?array
{
    return (new \Maruderm\WooCommerce\ProductBadges())->resolve($product);
}

/** @param int[] $ids @return array<int, array<string, mixed>> */
function maruderm_resolve_products_by_ids(array $ids, string $language = 'uk'): array
{
    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));

    if ($ids === []) {
        return [];
    }

    $repository = new \Maruderm\Catalog\CatalogRepository();
    $products = array_filter(array_map('wc_get_product', $ids), static fn ($product): bool => $product instanceof \WC_Product);

    return array_map(
        static fn (\WC_Product $product): array => maruderm_map_catalog_product(
            $repository,
            $product,
            $language
        ),
        $products
    );
}
