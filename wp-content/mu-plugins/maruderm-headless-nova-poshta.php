<?php
/**
 * Nova Poshta branch-delivery REST endpoints for the headless Next.js frontend.
 *
 * WooCommerce's Store API (which every other headless checkout call in this
 * build goes through) never fires wc-ukr-shipping's own field-persistence
 * hooks (woocommerce_checkout_create_order_shipping_item etc) -- those only
 * run for the legacy WC_Checkout::process_checkout() flow. So a customer's
 * chosen city/warehouse can't be delivered through the normal /checkout POST
 * at all; it has to be patched onto the order's shipping line item
 * afterward, replicating exactly what
 * NovaPoshta\Order\{CheckoutOrderHandler,CheckoutOrderShippingHandler} would
 * have written (see wp-content/plugins/wc-ukr-shipping/src/Component/Carriers/NovaPoshta/Order/).
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
    $cityName = (string) $request->get_param('cityName');
    $warehouseRef = (string) $request->get_param('warehouseRef');
    $warehouseName = (string) $request->get_param('warehouseName');

    if ($orderId <= 0 || $cityRef === '' || $warehouseRef === '') {
        return new WP_REST_Response(['error' => 'Некоректні дані відділення.'], 400);
    }

    $order = wc_get_order($orderId);

    if (! $order instanceof WC_Order || ! hash_equals($order->get_order_key(), $orderKey)) {
        return new WP_REST_Response(['error' => 'Замовлення не знайдено.'], 404);
    }

    $shippingItems = $order->get_items('shipping');
    $shippingItem = reset($shippingItems);

    if (! $shippingItem instanceof WC_Order_Item_Shipping) {
        return new WP_REST_Response(['error' => 'У замовленні немає способу доставки.'], 400);
    }

    // Clear any custom-address (courier) meta so the plugin's own address
    // mapper (used for TTN/waybill generation) doesn't see a mix of both
    // modes -- mirrors ShippingAddressMapper::mapPUDOAddress()'s own cleanup.
    foreach (['wcus_settlement_ref', 'wcus_settlement_full', 'wcus_settlement_name', 'wcus_settlement_area', 'wcus_settlement_region', 'wcus_street_ref', 'wcus_street_name', 'wcus_street_full', 'wcus_house', 'wcus_flat', 'wcus_api_address'] as $key) {
        $shippingItem->delete_meta_data($key);
    }

    $shippingItem->update_meta_data('wcus_city_ref', $cityRef);
    $shippingItem->update_meta_data('wcus_city_name', $cityName !== '' ? $cityName : '-');
    $shippingItem->update_meta_data('wcus_warehouse_ref', $warehouseRef);
    $shippingItem->update_meta_data('wcus_warehouse_name', $warehouseName !== '' ? $warehouseName : '-');
    $shippingItem->save();

    // Also reflect the real branch on the order's own address fields (what
    // the store owner sees in the native "Shipping address" block), on both
    // billing and shipping since this store collects a single address for
    // both (woocommerce_ship_to_destination = "billing").
    $order->set_billing_city($cityName);
    $order->set_billing_address_1($warehouseName);
    $order->set_shipping_city($cityName);
    $order->set_shipping_address_1($warehouseName);
    $order->update_meta_data('wcus_data_version', '3');
    $order->save();

    return new WP_REST_Response(['success' => true]);
}
