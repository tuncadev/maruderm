<?php
/**
 * Plugin Name: SmartyParcel (formerly WC Ukr Shipping)
 * Plugin URI: https://smartyparcel.com
 * Description: Multi-carrier order tracking and shipping solution for WooCommerce.
 * Version: 1.22.2
 * Author: kirillbdev
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-ukr-shipping
 * Domain Path: /lang
 * Requires PHP: 8.0
 * Tested up to: 7.1
 * WC tested up to: 11.0
*/

if ( ! defined('ABSPATH')) {
  exit;
}

define('WC_UKR_SHIPPING_PLUGIN_NAME', plugin_basename(__FILE__));
define('WC_UKR_SHIPPING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_UKR_SHIPPING_PLUGIN_ENTRY', __FILE__);
define('WC_UKR_SHIPPING_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('WCUS_TRANSLATE_DOMAIN', 'wc-ukr-shipping');
define('WCUS_MIGRATOR_HISTORY_KEY', 'wcus_migrations_history');

define('WC_UKR_SHIPPING_NP_SHIPPING_NAME', 'nova_poshta_shipping');
define('WC_UKR_SHIPPING_NP_SHIPPING_TITLE', 'Нова Пошта');

include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/constants.php';
include_once __DIR__ . '/globals.php';

add_action('before_woocommerce_init', function() {
    if (class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

kirillbdev\WCUkrShipping\Foundation\WCUkrShipping::instance()->init();