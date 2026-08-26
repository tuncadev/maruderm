<?php
/**
 * Private product cost and minimum-sale-price controls for WooCommerce.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/maruderm-product-pricing-controls/class-price-policy.php';
require_once __DIR__ . '/maruderm-product-pricing-controls/class-product-price-repository.php';
require_once __DIR__ . '/maruderm-product-pricing-controls/class-product-editor.php';
require_once __DIR__ . '/maruderm-product-pricing-controls/class-settings-page.php';
require_once __DIR__ . '/maruderm-product-pricing-controls/class-out-of-stock-pricing.php';
require_once __DIR__ . '/maruderm-product-pricing-controls/class-plugin.php';

add_action('plugins_loaded', ['Maruderm_Product_Pricing_Plugin', 'boot'], 20);
