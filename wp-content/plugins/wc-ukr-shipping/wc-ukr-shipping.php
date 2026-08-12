<?php
/**
 * Plugin Name: WC Ukraine Shipping / SmartyParcel
 * Plugin URI: https://smartyparcel.com
 * Description: Multi-carrier shipping solution for WooCommerce.
 * Version: 1.21.12
 * Author: kirillbdev
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires PHP: 7.4
 * Tested up to: 7.0
 * WC tested up to: 10.9
*/

if ( ! defined('ABSPATH')) {
  exit;
}

define('WC_UKR_SHIPPING_PLUGIN_NAME', plugin_basename(__FILE__));
define('WC_UKR_SHIPPING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_UKR_SHIPPING_PLUGIN_ENTRY', __FILE__);
define('WC_UKR_SHIPPING_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('WCUS_TRANSLATE_DOMAIN', 'wc-ukr-shipping-i18n');
define('WCUS_MIGRATOR_HISTORY_KEY', 'wcus_migrations_history');

define('WCUS_TRANSLATE_TYPE_PLUGIN', 0);
define('WCUS_TRANSLATE_TYPE_MO_FILE', 1);

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