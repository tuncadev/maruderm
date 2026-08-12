<?php

namespace kirillbdev\WCUkrShipping\States;

use kirillbdev\WCUkrShipping\Includes\AppState;
use kirillbdev\WCUkrShipping\Includes\Address\CheckoutFinder;
use kirillbdev\WCUkrShipping\Includes\UI\CityUIValue;
use kirillbdev\WCUkrShipping\Includes\UI\WarehouseUIValue;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutState extends AppState
{
    protected function getState(): array
    {
        $finder = new CheckoutFinder();
        $shippingTypes = [
            'warehouse' => 1,
        ];
        if ((int)wc_ukr_shipping_get_option('wc_ukr_shipping_address_shipping') === 1) {
            $shippingTypes['doors'] = 1;
        }
        if ((int)wc_ukr_shipping_get_option('wcus_show_poshtomats') === 1) {
            $shippingTypes['poshtomat'] = 1;
        }
        if ((int)wc_ukr_shipping_get_option('wcus_combine_poshtomats') === 1) {
            unset($shippingTypes['poshtomat']);
        }

        return [
            'city' => CityUIValue::fromFinder($finder),
            'warehouse' => WarehouseUIValue::fromFinder($finder),
            'shippingTypeDefault' => apply_filters('wcus_checkout_default_shipping_type', 'warehouse'),
            'shippingTypePriority' => apply_filters('wcus_checkout_shipping_type_priority', $shippingTypes),
        ];
    }
}