<?php

use kirillbdev\WCUkrShipping\Model\WCUSOrder;

if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! function_exists('wc_ukr_shipping_import_svg')) {

    function wc_ukr_shipping_import_svg($image)
    {
        return file_get_contents(WC_UKR_SHIPPING_PLUGIN_DIR . '/image/' . $image);
    }

}

if ( ! function_exists('wc_ukr_shipping_get_option')) {

    function wc_ukr_shipping_get_option($key)
    {
        return \kirillbdev\WCUkrShipping\DB\OptionsRepository::getOption($key);
    }

}

if ( ! function_exists('wc_ukr_shipping_is_checkout')) {

    function wc_ukr_shipping_is_checkout()
    {
        return function_exists('is_checkout') && is_checkout();
    }

}

if ( ! function_exists('wcus_container')) {

    function wcus_container(): \kirillbdev\WCUSCore\Foundation\Container
    {
        return \kirillbdev\WCUkrShipping\Foundation\WCUkrShipping::instance()->getContainer();
    }

}

if (!function_exists('wcus_wc_container_safe_get')) {

    function wcus_wc_container_safe_get(string $alias)
    {
        if (!function_exists('wc_get_container')) {
            return null;
        }

        try {
            return wc_get_container()->get($alias);
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('wcus_wrap_order')) {

    function wcus_wrap_order(\WC_Order $wcOrder): WCUSOrder
    {
        return new WCUSOrder($wcOrder);
    }
}

if (!function_exists('wcus_is_woocommerce_active')) {

    function wcus_is_woocommerce_active(): bool
    {
        return function_exists('WC');
    }

}