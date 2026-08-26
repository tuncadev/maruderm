<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class ShippingItemDrawer implements ModuleInterface
{
    /**
     * Boot function
     */
    public function init()
    {
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hideShippingMeta']);
        add_filter( 'woocommerce_order_item_display_meta_key', [$this, 'getOrderItemMetaKey']);
        add_filter( 'woocommerce_order_item_display_meta_value', [$this, 'getOrderItemMetaValue'], 10, 2);
    }

    public function hideShippingMeta(array $keys): array
    {
        $keys[] = 'wcus_settlement_ref';
        $keys[] = 'wcus_settlement_name';
        $keys[] = 'wcus_settlement_area';
        $keys[] = 'wcus_street_ref';
        $keys[] = 'wcus_street_name';
        $keys[] = 'wcus_api_address';
        $keys[] = 'wcus_settlement_region';
        $keys[] = 'wcus_area_ref';
        $keys[] = 'wcus_city_ref';
        $keys[] = 'wcus_warehouse_ref';
        $keys[] = 'wcus_ukrposhta_city_id';
        $keys[] = 'wcus_ukrposhta_warehouse_id';
        $keys[] = 'wcus_rozetka_city_id';
        $keys[] = 'wcus_rozetka_warehouse_id';

        return $keys;
    }

    public function getOrderItemMetaKey(string $key): string
    {
        $keyMap = [
            'wcus_city_name' => __('City', 'wc-ukr-shipping'),
            'wcus_warehouse_name' => __('Warehouse / Poshtomat', 'wc-ukr-shipping'),
            'wcus_settlement_full' => __('Settlement', 'wc-ukr-shipping'),
            'wcus_street_full' => __('Street', 'wc-ukr-shipping'),
            'wcus_house' => __('House number', 'wc-ukr-shipping'),
            'wcus_flat' => __('Flat', 'wc-ukr-shipping'),
            'wcus_ukrposhta_city_name' => __('City', 'wc-ukr-shipping'),
            'wcus_ukrposhta_warehouse_name' => __('Warehouse', 'wc-ukr-shipping'),
            'wcus_ukrposhta_service_type' => __('Service type', 'wc-ukr-shipping'),
            'wcus_rozetka_city_name' => __('City', 'wc-ukr-shipping'),
            'wcus_rozetka_warehouse_name' => __('Warehouse', 'wc-ukr-shipping'),
            WCUS_SHIPPING_META_VIEW_COST => __('Calculated shipping cost in checkout', 'wc-ukr-shipping'),
        ];

        return $keyMap[$key] ?? $key;
    }

    public function getOrderItemMetaValue($value, $meta): string
    {
        return isset($meta->key) && $meta->key === WCUS_SHIPPING_META_VIEW_COST
            ? wp_strip_all_tags(wc_price((float)$value))
            : $value;
    }
}
