<?php
/**
 * Account dashboard REST endpoints for the headless Next.js frontend.
 *
 * Reuses the live theme's own account business logic (Maruderm\Account\*,
 * Maruderm\WooCommerce\StockNotificationService) directly, the same way
 * every other headless mu-plugin reuses the theme's PHP classes -- so the
 * headless dashboard can never drift from the live site's own rules (loyalty
 * tier thresholds, allowed delivery types, avatar upload limits, etc).
 *
 * The theme's own AJAX handlers for these features (AccountAddresses,
 * AccountAvatars, StockNotifications) are wired to WordPress's cookie-based
 * `wp_ajax_*` actions + nonces tied to a browser session on the WP domain,
 * which our server-to-server Basic-Auth session never carries. These routes
 * are a parallel, Basic-Auth-gated entry point into the same service classes.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'maruderm_register_dashboard_routes');

function maruderm_register_dashboard_routes(): void
{
    register_rest_route('maruderm/v1', '/account/dashboard', [
        'methods' => 'GET',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_get_dashboard',
    ]);

    register_rest_route('maruderm/v1', '/account/addresses', [
        'methods' => 'POST',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_add_address',
    ]);

    register_rest_route('maruderm/v1', '/account/avatar', [
        'methods' => 'POST',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_upload_avatar',
    ]);

    register_rest_route('maruderm/v1', '/account/avatar/remove', [
        'methods' => 'POST',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_remove_avatar',
    ]);

    register_rest_route('maruderm/v1', '/account/notifications/toggle', [
        'methods' => 'POST',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_toggle_notification',
    ]);
}

function maruderm_handle_get_dashboard(WP_REST_Request $request): WP_REST_Response
{
    $userId = get_current_user_id();
    $addressService = new \Maruderm\Account\AccountAddressService();
    $avatarService = new \Maruderm\Account\AccountAvatarService();
    $bonusService = new \Maruderm\Account\BonusService();
    $notificationService = new \Maruderm\WooCommerce\StockNotificationService();

    $notificationProductIds = array_map(
        static fn (WC_Product $product): int => $product->get_id(),
        $notificationService->subscriptionsForUser($userId)
    );

    return new WP_REST_Response([
        'loyalty' => $bonusService->summary($userId),
        'addresses' => $addressService->addressesForUser($userId),
        'avatarUrl' => $avatarService->url($userId),
        'notificationProductIds' => $notificationProductIds,
    ]);
}

function maruderm_handle_add_address(WP_REST_Request $request): WP_REST_Response
{
    $addressService = new \Maruderm\Account\AccountAddressService();

    try {
        $addressService->add(
            get_current_user_id(),
            (string) $request->get_param('type'),
            (string) $request->get_param('city'),
            (string) $request->get_param('location')
        );
    } catch (\InvalidArgumentException $exception) {
        return new WP_REST_Response(['error' => $exception->getMessage()], 422);
    }

    return new WP_REST_Response(['addresses' => $addressService->addressesForUser(get_current_user_id())]);
}

function maruderm_handle_upload_avatar(WP_REST_Request $request): WP_REST_Response
{
    $avatarService = new \Maruderm\Account\AccountAvatarService();
    $file = isset($_FILES['avatar']) && is_array($_FILES['avatar']) ? $_FILES['avatar'] : [];
    $url = $avatarService->upload(get_current_user_id(), $file);

    if (is_wp_error($url)) {
        return new WP_REST_Response(['error' => $url->get_error_message()], 422);
    }

    return new WP_REST_Response(['avatarUrl' => $url]);
}

function maruderm_handle_remove_avatar(WP_REST_Request $request): WP_REST_Response
{
    (new \Maruderm\Account\AccountAvatarService())->remove(get_current_user_id());

    return new WP_REST_Response(['avatarUrl' => '']);
}

function maruderm_handle_toggle_notification(WP_REST_Request $request): WP_REST_Response
{
    $notificationService = new \Maruderm\WooCommerce\StockNotificationService();
    $userId = get_current_user_id();
    $productId = (int) $request->get_param('productId');
    $result = $notificationService->toggle($productId, $userId);

    if (is_wp_error($result)) {
        return new WP_REST_Response(['error' => $result->get_error_message()], 422);
    }

    $productIds = array_map(
        static fn (WC_Product $product): int => $product->get_id(),
        $notificationService->subscriptionsForUser($userId)
    );

    return new WP_REST_Response(['active' => $result, 'notificationProductIds' => $productIds]);
}
