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
            'language' => ['type' => 'String'],
        ],
        'resolve' => static fn ($root, array $args) => maruderm_resolve_product(
            (string) $args['slug'],
            (string) ($args['language'] ?? 'uk')
        ),
    ]);
}

function maruderm_resolve_product(string $slug, string $language = 'uk'): ?array
{
    if ($slug === '') {
        return null;
    }

    $identityResolver = new \Maruderm\Multilingual\ProductIdentityResolver();
    $identity = $identityResolver->resolveBySlug($slug, $language);
    $product = $identity !== null ? wc_get_product($identity['canonicalDatabaseId']) : null;

    if (! $product instanceof WC_Product) {
        return null;
    }

    $presentation = $identityResolver->presentationPost($product->get_id(), $language);

    if (! $presentation instanceof WP_Post) {
        return null;
    }

    $content = new \Maruderm\WooCommerce\SingleProductContent();
    $imageRepository = new \Maruderm\WooCommerce\ProductImageRepository();
    $catalogRepository = new \Maruderm\Catalog\CatalogRepository();
    $taxonomyResolver = new \Maruderm\Multilingual\TaxonomyPresentationResolver();

    $category = $content->category($product);
    $localizedCategory = $category instanceof WP_Term
        ? $taxonomyResolver->translateTerm($category, $language)
        : null;
    $categoryUrl = $category instanceof WP_Term ? get_term_link($category) : wc_get_page_permalink('shop');
    $categoryUrl = is_wp_error($categoryUrl) ? wc_get_page_permalink('shop') : $categoryUrl;
    if ($localizedCategory instanceof WP_Term && $language === 'ru') {
        $categoryUrl = home_url('/ru/catalog/' . $localizedCategory->slug . '/');
    }

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

    $description = trim($presentation->post_content) !== ''
        ? $presentation->post_content
        : $product->get_description();
    $lead = trim($presentation->post_excerpt) !== ''
        ? $presentation->post_excerpt
        : wp_trim_words(wp_strip_all_tags($description), 32);

    return [
        'databaseId' => $product->get_id(),
        'name' => $presentation->post_title,
        'slug' => $presentation->post_name,
        'url' => $identity['resolvedLanguage'] === 'ru'
            ? home_url('/ru/tovar/' . $presentation->post_name . '/')
            : $product->get_permalink(),
        'sku' => trim($product->get_sku()),
        'priceHtml' => $product->get_price_html(),
        'lead' => $lead,
        'descriptionHtml' => wpautop($description),
        'inStock' => $product->is_in_stock(),
        'purchasable' => $product->is_purchasable(),
        'ratingAverage' => (float) $product->get_average_rating(),
        'reviewCount' => $product->get_review_count(),
        'categoryName' => $localizedCategory->name ?? $category->name ?? 'Maruderm',
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
            static fn (WC_Product $related): array => maruderm_map_catalog_product(
                $catalogRepository,
                $related,
                $language
            ),
            $content->related($product)
        ),
    ];
}
