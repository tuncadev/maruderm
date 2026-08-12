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
            'wcus_city_name' => __('City', 'wc-ukr-shipping-i18n'),
            'wcus_warehouse_name' => __('Warehouse / Poshtomat', 'wc-ukr-shipping-i18n'),
            'wcus_settlement_full' => __('Settlement', 'wc-ukr-shipping-i18n'),
            'wcus_street_full' => __('Street', 'wc-ukr-shipping-i18n'),
            'wcus_house' => __('House number', 'wc-ukr-shipping-i18n'),
            'wcus_flat' => __('Flat', 'wc-ukr-shipping-i18n'),
            'wcus_ukrposhta_city_name' => __('City', 'wc-ukr-shipping-i18n'),
            'wcus_ukrposhta_warehouse_name' => __('Warehouse', 'wc-ukr-shipping-i18n'),
            'wcus_ukrposhta_service_type' => __('Service type', 'wc-ukr-shipping-i18n'),
            'wcus_rozetka_city_name' => __('City', 'wc-ukr-shipping-i18n'),
            'wcus_rozetka_warehouse_name' => __('Warehouse', 'wc-ukr-shipping-i18n'),
        ];

        return $keyMap[$key] ?? $key;
    }
}
