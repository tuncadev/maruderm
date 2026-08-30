<?php
/**
 * Wishlist REST endpoints for the headless Next.js frontend.
 *
 * Only serves logged-in users (authenticated the same way as everything
 * else headless: HTTP Basic Auth via an Application Password). Guest
 * wishlists in wcboost-wishlist are tied to a browser session cookie that
 * our server-to-server requests never carry, so guests are handled entirely
 * client-side via localStorage instead -- these endpoints are never called
 * for a signed-out visitor.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'maruderm_register_wishlist_routes');

function maruderm_register_wishlist_routes(): void
{
    register_rest_route('maruderm/v1', '/wishlist', [
        'methods' => 'GET',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_get_wishlist',
    ]);

    register_rest_route('maruderm/v1', '/wishlist/toggle', [
        'methods' => 'POST',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_toggle_wishlist',
    ]);
}

function maruderm_handle_get_wishlist(WP_REST_Request $request): WP_REST_Response
{
    return new WP_REST_Response(['items' => maruderm_wishlist_product_ids()]);
}

function maruderm_handle_toggle_wishlist(WP_REST_Request $request): WP_REST_Response
{
    if (! class_exists(\WCBoost\Wishlist\Helper::class)) {
        return new WP_REST_Response(['error' => 'Wishlist plugin unavailable.'], 500);
    }

    $productId = (int) $request->get_param('productId');
    $product = $productId > 0 ? wc_get_product($productId) : null;

    if (! $product instanceof WC_Product) {
        return new WP_REST_Response(['error' => 'Invalid product.'], 400);
    }

    $wishlist = \WCBoost\Wishlist\Helper::get_wishlist();

    // Wishlist::has_product() compares with ===, but get_product_id() can
    // come back as a numeric string from the data store, so it never
    // matches our int here. Find the item ourselves with a type-safe check.
    $existingKey = null;

    foreach ($wishlist->get_items() as $key => $item) {
        if ((int) $item->get_product_id() === $productId) {
            $existingKey = $key;
            break;
        }
    }

    if ($existingKey !== null) {
        $wishlist->remove_item($existingKey);
    } else {
        $wishlist->add_item(new \WCBoost\Wishlist\Wishlist_Item($product));
    }

    $wishlist->save();

    return new WP_REST_Response(['items' => maruderm_wishlist_product_ids()]);
}

/** @return int[] */
function maruderm_wishlist_product_ids(): array
{
    if (! class_exists(\WCBoost\Wishlist\Helper::class)) {
        return [];
    }

    $wishlist = \WCBoost\Wishlist\Helper::get_wishlist();
    $ids = [];

    foreach ($wishlist->get_items() as $item) {
        $ids[] = $item->get_product_id();
    }

    return $ids;
}
