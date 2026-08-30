<?php
/**
 * Exposes the maruderm.dev header (logo, search, account/wishlist/cart links,
 * and the mega menu) through WPGraphQL for the headless Next.js frontend.
 *
 * Reuses the same category/ACF logic as
 * wp-content/themes/maruderm/components/menu/menu.php so the headless mega
 * menu always matches the live PHP-rendered one.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', 'maruderm_register_header_graphql');

function maruderm_register_header_graphql(): void
{
    register_graphql_object_type('MarudermSiteChrome', [
        'description' => 'Header-level site chrome: logo and account/wishlist/cart/search links.',
        'fields' => [
            'siteName' => ['type' => 'String'],
            'logoUrl' => ['type' => 'String'],
            'searchActionUrl' => ['type' => 'String'],
            'accountUrl' => ['type' => 'String'],
            'wishlistUrl' => ['type' => 'String'],
            'cartUrl' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermSearchCategory', [
        'description' => 'One entry in the flattened header search category dropdown.',
        'fields' => [
            'databaseId' => ['type' => 'Int'],
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'depth' => ['type' => 'Int'],
        ],
    ]);

    register_graphql_object_type('MarudermMegaMenuGroup', [
        'description' => 'One labeled column of child-category links inside a mega menu dropdown.',
        'fields' => [
            'label' => ['type' => 'String'],
            'items' => ['type' => ['list_of' => 'MarudermMegaMenuLink']],
        ],
    ]);

    register_graphql_object_type('MarudermMegaMenuLink', [
        'fields' => [
            'name' => ['type' => 'String'],
            'url' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermMegaMenuItem', [
        'description' => 'One top-level category in the header mega menu.',
        'fields' => [
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'tone' => ['type' => 'String'],
            'eyebrow' => ['type' => 'String'],
            'title' => ['type' => 'String'],
            'imageUrl' => ['type' => 'String'],
            'groups' => ['type' => ['list_of' => 'MarudermMegaMenuGroup']],
        ],
    ]);

    register_graphql_field('RootQuery', 'marudermSiteChrome', [
        'type' => 'MarudermSiteChrome',
        'resolve' => static fn () => maruderm_resolve_site_chrome(),
    ]);

    register_graphql_field('RootQuery', 'marudermSearchCategories', [
        'type' => ['list_of' => 'MarudermSearchCategory'],
        'resolve' => static fn () => maruderm_resolve_search_categories(),
    ]);

    register_graphql_field('RootQuery', 'marudermMegaMenu', [
        'type' => ['list_of' => 'MarudermMegaMenuItem'],
        'resolve' => static fn () => maruderm_resolve_mega_menu(),
    ]);
}

function maruderm_resolve_site_chrome(): array
{
    $accountUrl = function_exists('wc_get_page_permalink')
        ? wc_get_page_permalink('myaccount')
        : home_url('/my-account/');

    $wishlistPageId = (int) get_option('wcboost_wishlist_page_id');
    $wishlistUrl = $wishlistPageId > 0 ? get_permalink($wishlistPageId) : home_url('/wishlist/');

    $cartUrl = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

    return [
        'siteName' => get_bloginfo('name'),
        'logoUrl' => (string) get_theme_mod('logo'),
        'searchActionUrl' => home_url('/'),
        'accountUrl' => is_string($accountUrl) ? $accountUrl : home_url('/my-account/'),
        'wishlistUrl' => is_string($wishlistUrl) ? $wishlistUrl : home_url('/wishlist/'),
        'cartUrl' => $cartUrl,
    ];
}

/** @return array<int, array<string, mixed>> */
function maruderm_resolve_search_categories(): array
{
    $topLevel = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => 0,
        'hide_empty' => true,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);

    if (is_wp_error($topLevel) || $topLevel === []) {
        return [];
    }

    $rows = [];

    foreach ($topLevel as $parent) {
        $rows[] = [
            'databaseId' => $parent->term_id,
            'name' => $parent->name,
            'slug' => $parent->slug,
            'depth' => 0,
        ];

        $children = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $parent->term_id,
            'hide_empty' => true,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        if (is_wp_error($children)) {
            continue;
        }

        foreach ($children as $child) {
            $rows[] = [
                'databaseId' => $child->term_id,
                'name' => $child->name,
                'slug' => $child->slug,
                'depth' => 1,
            ];
        }
    }

    return $rows;
}

/** @return array<int, array<string, mixed>> */
function maruderm_resolve_mega_menu(): array
{
    $topLevel = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => 0,
        'hide_empty' => true,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);

    if (is_wp_error($topLevel) || $topLevel === []) {
        return [];
    }

    $topLevel = array_values($topLevel);
    $tones = ['coral', 'yellow', 'mint', 'lilac', 'blue'];
    $groupLabels = ['Категорії', 'Обирають часто', 'Ще більше'];
    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $items = [];

    foreach ($topLevel as $index => $term) {
        $termUrl = get_term_link($term);
        $termUrl = is_wp_error($termUrl) ? $shopUrl : $termUrl;

        $children = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $term->term_id,
            'hide_empty' => true,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);
        $children = is_wp_error($children) ? [] : array_values($children);

        $customImageId = function_exists('get_field') ? (int) get_field('submenu_panel_image', $term) : 0;
        $customEyebrow = function_exists('get_field') ? trim((string) get_field('submenu_panel_eyebrow', $term)) : '';
        $customText = function_exists('get_field') ? trim((string) get_field('submenu_panel_text', $term)) : '';
        $description = trim(wp_strip_all_tags((string) $term->description));

        $title = $customText !== ''
            ? $customText
            : ($description !== '' ? wp_trim_words($description, 9, '…') : $term->name);
        $eyebrow = $customEyebrow !== '' ? $customEyebrow : number_format_i18n($term->count) . ' товарів';

        $imageId = $customImageId > 0 ? $customImageId : (int) get_term_meta($term->term_id, 'thumbnail_id', true);
        $imageUrl = $imageId > 0 ? wp_get_attachment_image_url($imageId, 'medium') : false;

        if (! $imageUrl && function_exists('wc_get_products')) {
            $featured = wc_get_products([
                'status' => 'publish',
                'limit' => 1,
                'category' => [$term->slug],
                'orderby' => 'popularity',
                'return' => 'objects',
            ]);

            if ($featured !== [] && $featured[0] instanceof WC_Product) {
                $imgId = $featured[0]->get_image_id();
                $imageUrl = $imgId ? wp_get_attachment_image_url($imgId, 'medium') : false;
            }
        }

        $groups = [];

        if ($children !== []) {
            $chunks = array_chunk($children, (int) ceil(count($children) / 3));

            foreach ($chunks as $groupIndex => $chunk) {
                $groups[] = [
                    'label' => $groupLabels[$groupIndex] ?? 'Категорії',
                    'items' => array_map(static function (\WP_Term $child) use ($shopUrl): array {
                        $childUrl = get_term_link($child);

                        return [
                            'name' => $child->name,
                            'url' => is_wp_error($childUrl) ? $shopUrl : $childUrl,
                        ];
                    }, $chunk),
                ];
            }
        }

        $items[] = [
            'name' => $term->name,
            'slug' => $term->slug,
            'url' => $termUrl,
            'tone' => $tones[$index % count($tones)],
            'eyebrow' => $eyebrow,
            'title' => $title,
            'imageUrl' => is_string($imageUrl) ? $imageUrl : '',
            'groups' => $groups,
        ];
    }

    return $items;
}
