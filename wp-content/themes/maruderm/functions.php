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
add_filter('woocommerce_attribute_show_in_nav_menus', '__return_true');
add_filter('martfury_get_sticky_header', '__return_false', 100);
