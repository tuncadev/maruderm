<?php
/**
 * Theme entry point.
 *
 * @package Maruderm
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/app/Bootstrap.php';

if (!function_exists('martfury_extra_cart')) {
    /** Render the child theme's live WooCommerce mini-cart in Martfury header layouts. */
    function martfury_extra_cart()
    {
        \Maruderm\WooCommerce\HeaderMiniCart::render();
    }
}

add_filter('woocommerce_attribute_show_in_nav_menus', '__return_true');
add_filter('martfury_get_sticky_header', '__return_false', 100);
