<?php
/**
 * Exposes a single maruderm.dev product (gallery, summary, specifications,
 * benefits, ingredients, routine, reviews, related products) through
 * WPGraphQL for the headless Next.js frontend.
 *
 * Reuses Maruderm\WooCommerce\SingleProductContent and
 * Maruderm\WooCommerce\ProductImageRepository's public methods so the
 * headless product page matches the live PHP-rendered one.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', 'maruderm_register_product_graphql');

function maruderm_register_product_graphql(): void
{
    register_graphql_object_type('MarudermProductImage', [
        'fields' => [
            'key' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'thumbnailUrl' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermTextPair', [
        'fields' => [
            'title' => ['type' => 'String'],
            'text' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermProductReview', [
        'fields' => [
            'authorName' => ['type' => 'String'],
            'content' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermProductDetail', [
        'fields' => [
            'databaseId' => ['type' => 'Int'],
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'sku' => ['type' => 'String'],
            'priceHtml' => ['type' => 'String'],
            'lead' => ['type' => 'String'],
            'descriptionHtml' => ['type' => 'String'],
            'inStock' => ['type' => 'Boolean'],
            'purchasable' => ['type' => 'Boolean'],
            'ratingAverage' => ['type' => 'Float'],
            'reviewCount' => ['type' => 'Int'],
            'categoryName' => ['type' => 'String'],
            'categoryUrl' => ['type' => 'String'],
            'images' => ['type' => ['list_of' => 'MarudermProductImage']],
            'highlights' => ['type' => ['list_of' => 'String']],
            'fullIngredients' => ['type' => 'String'],
            'netWeight' => ['type' => 'String'],
            'boxDimensions' => ['type' => 'String'],
            'origin' => ['type' => 'String'],
            'shelfLife' => ['type' => 'String'],
            'benefits' => ['type' => ['list_of' => 'MarudermTextPair']],
            'ingredients' => ['type' => ['list_of' => 'MarudermTextPair']],
            'formula' => ['type' => 'String'],
            'routine' => ['type' => ['list_of' => 'MarudermTextPair']],
            'review' => ['type' => 'MarudermProductReview'],
            'related' => ['type' => ['list_of' => 'MarudermCatalogProduct']],
        ],
    ]);

    register_graphql_field('RootQuery', 'marudermProduct', [
        'type' => 'MarudermProductDetail',
        'args' => [
            'slug' => ['type' => ['non_null' => 'String']],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_product((string) $args['slug']),
    ]);
}

function maruderm_resolve_product(string $slug): ?array
{
    if ($slug === '') {
        return null;
    }

    $post = get_page_by_path($slug, OBJECT, 'product');
    $product = $post instanceof WP_Post && $post->post_status === 'publish'
        ? wc_get_product($post->ID)
        : null;

    if (! $product instanceof WC_Product) {
        return null;
    }

    $content = new \Maruderm\WooCommerce\SingleProductContent();
    $imageRepository = new \Maruderm\WooCommerce\ProductImageRepository();
    $catalogRepository = new \Maruderm\Catalog\CatalogRepository();

    $category = $content->category($product);
    $categoryUrl = $category instanceof WP_Term ? get_term_link($category) : wc_get_page_permalink('shop');
    $categoryUrl = is_wp_error($categoryUrl) ? wc_get_page_permalink('shop') : $categoryUrl;

    $images = array_map(static function (array $image): array {
        return [
            'key' => (string) $image['key'],
            'url' => (string) $image['url'],
            'thumbnailUrl' => (string) $image['thumbnail_url'],
        ];
    }, $imageRepository->images($product));

    $comments = get_comments([
        'post_id' => $product->get_id(),
        'status' => 'approve',
        'number' => 1,
        'type' => 'review',
    ]);
    $comment = $comments[0] ?? null;
    $review = $comment instanceof WP_Comment
        ? ['authorName' => $comment->comment_author, 'content' => wp_strip_all_tags($comment->comment_content)]
        : null;

    $description = $product->get_description() !== '' ? $product->get_description() : $content->lead($product);

    return [
        'databaseId' => $product->get_id(),
        'name' => $product->get_name(),
        'slug' => $product->get_slug(),
        'url' => $product->get_permalink(),
        'sku' => trim($product->get_sku()),
        'priceHtml' => $product->get_price_html(),
        'lead' => $content->lead($product),
        'descriptionHtml' => wpautop($description),
        'inStock' => $product->is_in_stock(),
        'purchasable' => $product->is_purchasable(),
        'ratingAverage' => (float) $product->get_average_rating(),
        'reviewCount' => $product->get_review_count(),
        'categoryName' => $category->name ?? 'Maruderm',
        'categoryUrl' => $categoryUrl,
        'images' => $images,
        'highlights' => $content->highlights($product),
        'fullIngredients' => $content->fullIngredients($product),
        'netWeight' => $content->netWeight($product),
        'boxDimensions' => $content->boxDimensions($product),
        'origin' => $content->origin($product),
        'shelfLife' => $content->shelfLife($product),
        'benefits' => $content->benefits($product),
        'ingredients' => $content->ingredients($product),
        'formula' => $content->formula($product),
        'routine' => $content->routine($product),
        'review' => $review,
        'related' => array_map(
            static fn (WC_Product $related): array => maruderm_map_catalog_product($catalogRepository, $related),
            $content->related($product)
        ),
    ];
}
