<?php
/**
 * Nova Poshta branch-delivery REST endpoints for the headless Next.js frontend.
 *
 * Store API checkout persists validated carrier IDs before CRM transfer.
 * The order-key endpoint remains available for older storefronts.
 *
 * City/warehouse search reuses the plugin's own DB-backed repositories
 * directly (same data the site's own admin-ajax lookup uses) rather than
 * proxying wp-admin/admin-ajax.php, which requires a per-user CSRF nonce
 * that a stateless headless client has no natural way to carry.
 *
 * The order-patch endpoint is intentionally public (no login required) and
 * authorizes via the order's own order_key, the same secret WooCommerce
 * itself uses for guest "view order" links -- this endpoint must work for
 * guest checkouts too, and a WordPress capability check would not (a
 * customer's Application Password cannot edit shop_order posts; only
 * shop_manager/administrator can).
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/maruderm-nova-poshta/class-checkout.php';
(new Maruderm_Nova_Poshta_Checkout())->register();

add_action('rest_api_init', 'maruderm_register_nova_poshta_routes');

function maruderm_register_nova_poshta_routes(): void
{
    register_rest_route('maruderm/v1', '/nova-poshta/cities', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'maruderm_handle_search_np_cities',
    ]);

    register_rest_route('maruderm/v1', '/nova-poshta/warehouses', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'maruderm_handle_search_np_warehouses',
    ]);

    register_rest_route('maruderm/v1', '/nova-poshta/apply', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'maruderm_handle_apply_np_warehouse',
    ]);
}

function maruderm_np_available(): bool
{
    return class_exists(\kirillbdev\WCUkrShipping\Component\Shipping\NovaPoshtaPUDOProvider::class);
}

function maruderm_np_provider(): \kirillbdev\WCUkrShipping\Component\Shipping\NovaPoshtaPUDOProvider
{
    return new \kirillbdev\WCUkrShipping\Component\Shipping\NovaPoshtaPUDOProvider(
        new \kirillbdev\WCUkrShipping\DB\Repositories\CityRepository(),
        new \kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository()
    );
}

function maruderm_handle_search_np_cities(WP_REST_Request $request): WP_REST_Response
{
    if (! maruderm_np_available()) {
        return new WP_REST_Response(['error' => 'Служба доставки недоступна.'], 500);
    }

    $query = (string) $request->get_param('query');

    if (mb_strlen($query) < 2) {
        return new WP_REST_Response(['items' => []]);
    }

    $cities = maruderm_np_provider()->searchCitiesByQuery($query);

    return new WP_REST_Response([
        'items' => array_map(
            static fn (\kirillbdev\WCUkrShipping\Dto\Shipping\City $city): array => [
                'ref' => $city->id,
                'name' => $city->nameUa,
                'state' => (new Maruderm_Nova_Poshta_Checkout())->stateForCity($city->id),
            ],
            array_slice($cities, 0, 15)
        ),
    ]);
}

function maruderm_handle_search_np_warehouses(WP_REST_Request $request): WP_REST_Response
{
    if (! maruderm_np_available()) {
        return new WP_REST_Response(['error' => 'Служба доставки недоступна.'], 500);
    }

    $cityRef = (string) $request->get_param('cityRef');

    if ($cityRef === '') {
        return new WP_REST_Response(['error' => 'Спочатку обери місто.'], 400);
    }

    $page = max(1, (int) $request->get_param('page'));
    $query = (string) $request->get_param('query');

    $result = maruderm_np_provider()->searchPUDOByQuery(
        new \kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO(
            $cityRef,
            $query,
            [
                \kirillbdev\WCUkrShipping\Dto\Shipping\PUDO::PUDO_TYPE_WAREHOUSE,
                \kirillbdev\WCUkrShipping\Dto\Shipping\PUDO::PUDO_TYPE_LOCKER,
            ],
            null,
            $page
        )
    );

    return new WP_REST_Response([
        'items' => array_map(
            static fn (\kirillbdev\WCUkrShipping\Dto\Shipping\PUDO $pudo): array => [
                'ref' => $pudo->id,
                'name' => $pudo->nameUa,
                'type' => $pudo->type,
            ],
            $result['data']
        ),
        'hasMore' => $page * 20 < $result['total'],
    ]);
}

function maruderm_handle_apply_np_warehouse(WP_REST_Request $request): WP_REST_Response
{
    $orderId = (int) $request->get_param('orderId');
    $orderKey = (string) $request->get_param('orderKey');
    $cityRef = (string) $request->get_param('cityRef');
    $warehouseRef = (string) $request->get_param('warehouseRef');

    if ($orderId <= 0 || $cityRef === '' || $warehouseRef === '') {
        return new WP_REST_Response(['error' => 'Некоректні дані відділення.'], 400);
    }

    $order = wc_get_order($orderId);

    if (! $order instanceof WC_Order || ! hash_equals($order->get_order_key(), $orderKey)) {
        return new WP_REST_Response(['error' => 'Замовлення не знайдено.'], 404);
    }

    try {
        $checkout = new Maruderm_Nova_Poshta_Checkout();
        $checkout->apply($order, $checkout->resolve($cityRef, $warehouseRef));
        $order->save();
    } catch (\InvalidArgumentException $error) {
        return new WP_REST_Response(['error' => 'Некоректні дані відділення.'], 400);
    }

    return new WP_REST_Response(['success' => true]);
}
