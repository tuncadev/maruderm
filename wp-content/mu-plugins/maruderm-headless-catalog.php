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
        ],
    ]);

    register_graphql_object_type('MarudermCatalogFilterOption', [
        'fields' => [
            'value' => ['type' => 'String'],
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
        'resolve' => static fn () => maruderm_resolve_catalog(),
    ]);

    register_graphql_field('RootQuery', 'marudermProductsByIds', [
        'type' => ['list_of' => 'MarudermCatalogProduct'],
        'args' => [
            'ids' => ['type' => ['non_null' => ['list_of' => 'Int']]],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_products_by_ids($args['ids']),
    ]);

    register_graphql_field('RootQuery', 'marudermProductSearch', [
        'type' => ['list_of' => 'MarudermCatalogProduct'],
        'args' => [
            'term' => ['type' => ['non_null' => 'String']],
            'limit' => ['type' => 'Int'],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_product_search(
            (string) $args['term'],
            isset($args['limit']) ? (int) $args['limit'] : 6
        ),
    ]);
}

/** @return array<int, array<string, mixed>> */
function maruderm_resolve_product_search(string $term, int $limit): array
{
    $term = trim($term);

    if (mb_strlen($term) < 3) {
        return [];
    }

    $repository = new \Maruderm\Catalog\CatalogRepository();
    $matches = [];

    foreach ($repository->products() as $product) {
        if (mb_stripos($product->get_name(), $term) !== false) {
            $matches[] = maruderm_map_catalog_product($repository, $product);

            if (count($matches) >= $limit) {
                break;
            }
        }
    }

    return $matches;
}

function maruderm_resolve_catalog(): array
{
    $repository = new \Maruderm\Catalog\CatalogRepository();
    $products = $repository->products();

    return [
        'products' => array_map(
            static fn (\WC_Product $product): array => maruderm_map_catalog_product($repository, $product),
            $products
        ),
        'categoryOptions' => $repository->categoryOptions($products),
        'skinTypeOptions' => $repository->attributeOptions($products, 'pa_skin_type'),
        'concernOptions' => $repository->attributeOptions($products, 'pa_skin_problem'),
        'hairNeedOptions' => $repository->attributeOptions($products, 'pa_hair_need'),
        'navigationCategories' => array_map(
            static fn (array $category): array => [
                'value' => $category['value'],
                'label' => $category['label'],
                'url' => $category['url'],
                'imageUrl' => $category['image'],
                'tone' => $category['tone'],
            ],
            $repository->navigationCategories()
        ),
    ];
}

function maruderm_map_catalog_product(\Maruderm\Catalog\CatalogRepository $repository, \WC_Product $product): array
{
    $imageId = $product->get_image_id();
    $imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'woocommerce_thumbnail') : false;
    $topCategories = $repository->topCategories($product);
    $categoryLabel = $topCategories[0]->name ?? 'Maruderm';
    $createdAt = $product->get_date_created();

    return [
        'databaseId' => $product->get_id(),
        'name' => $product->get_name(),
        'slug' => $product->get_slug(),
        'url' => $product->get_permalink(),
        'imageUrl' => is_string($imageUrl) ? $imageUrl : wc_placeholder_img_src('woocommerce_thumbnail'),
        'imageAlt' => wp_strip_all_tags($product->get_name()),
        'priceHtml' => $product->get_price_html(),
        'price' => $product->is_in_stock() ? (float) $product->get_price() : null,
        'categoryLabel' => $categoryLabel,
        'categorySlugs' => $repository->categorySlugs($product),
        'skinTypeSlugs' => $repository->termSlugs($product, 'pa_skin_type'),
        'concernSlugs' => $repository->termSlugs($product, 'pa_skin_problem'),
        'hairNeedSlugs' => $repository->termSlugs($product, 'pa_hair_need'),
        'popularity' => $product->get_total_sales(),
        'createdTimestamp' => $createdAt !== null ? $createdAt->getTimestamp() : 0,
        'inStock' => $product->is_in_stock(),
    ];
}

/** @param int[] $ids @return array<int, array<string, mixed>> */
function maruderm_resolve_products_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));

    if ($ids === []) {
        return [];
    }

    $repository = new \Maruderm\Catalog\CatalogRepository();
    $products = array_filter(array_map('wc_get_product', $ids), static fn ($product): bool => $product instanceof \WC_Product);

    return array_map(
        static fn (\WC_Product $product): array => maruderm_map_catalog_product($repository, $product),
        $products
    );
}
