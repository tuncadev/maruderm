<?php
/**
 * Plugin Name: WooCommerce Keycrm.app
 * Plugin URI: https://keycrm.app/
 * Description: Integration plugin for WooCommerce & Keycrm.app
 * Author: KeyCRM.app
 * Author URI: https://keycrm.app/
 * Version: 1.0.24
 * Text Domain: keycrm
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists( 'WC_Integration_Keycrm')) :

    /**
     * Class WC_Integration_Keycrm
     */
    class WC_Integration_Keycrm {
        const WOOCOMMERCE_SLUG = 'woocommerce';
        const WOOCOMMERCE_PLUGIN_PATH = 'woocommerce/woocommerce.php';

        private static $instance;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Construct the plugin.
         */
        public function __construct() {
            $this->load_plugin_textdomain();

            if (class_exists( 'WC_Integration' )) {
                self::load_module();
                add_filter('woocommerce_integrations', array( $this, 'add_integration'));
                add_action('rest_api_init', array($this, 'register_rest_routes'));
            } else {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            }

            $this->init_hpos_support();
        }

        /**
         * Register REST API routes
         */
        public function register_rest_routes() {
            register_rest_route('keycrm/v1', '/webhook', array(
                'methods' => 'POST',
                'callback' => array(WC_Keycrm_Webhook_Handler::class, 'handle_rest_webhook'),
                'permission_callback' => '__return_true'
            ));
        }

        /**
         * Init HPOS support
         */
        public function init_hpos_support() {
            add_action( 'before_woocommerce_init', function() {
                if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                        'custom_order_tables',
                        __FILE__,
                        true
                    );
                }
            });
        }

        public function woocommerce_missing_notice() {
            if (static::isWooCommerceInstalled()) {
                if (!is_plugin_active(static::WOOCOMMERCE_PLUGIN_PATH)) {
                    echo '
            <div class="error">
                <p>
                    ' . sprintf(
                            __('Activate WooCommerce in order to enable KeyCRM integration! <a href="%s" aria-label="Activate WooCommerce">Click here to open plugins manager</a>', 'keycrm'),
                            wp_nonce_url(admin_url('plugins.php'))
                        ) . '
                </p>
            </div>
            ';
                }
            } else {
                echo '
        <div class="error">
            <p>
                ' . sprintf(
                        __('Install <a href="%s" aria-label="Install WooCommerce">WooCommerce</a> in order to enable KeyCRM integration!', 'keycrm'),
                        static::generatePluginInstallationUrl(static::WOOCOMMERCE_SLUG)
                    ) . '
            </p>
        </div>
        ';
            }
        }

        public function load_plugin_textdomain() {
            load_plugin_textdomain('keycrm', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * Add a new integration to WooCommerce.
         *
         * @param $integrations
         *
         * @return array
         */
        public function add_integration( $integrations ) {
            $integrations[] = 'WC_Keycrm_Base';
            return $integrations;
        }

        /**
         * Loads module classes.
         */
        public static function load_module()
        {
            require_once(self::checkCustomFile('include/interfaces/class-wc-keycrm-builder-interface.php'));
            require_once(self::checkCustomFile('include/models/class-wc-keycrm-customer-switcher-state.php'));
            require_once(self::checkCustomFile('include/models/class-wc-keycrm-customer-switcher-result.php'));
            require_once(self::checkCustomFile('include/components/class-wc-keycrm-logger.php'));
            require_once(self::checkCustomFile('include/components/class-wc-keycrm-history-assembler.php'));
            require_once(self::checkCustomFile('include/components/class-wc-keycrm-paginated-request.php'));
            require_once(self::checkCustomFile('include/components/class-wc-keycrm-customer-switcher.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-keycrm-abstract-builder.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-keycrm-abstracts-settings.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-keycrm-abstracts-data.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-keycrm-abstracts-address.php'));
            require_once(self::checkCustomFile('include/customer/woocommerce/class-wc-keycrm-wc-customer-builder.php'));
            require_once(self::checkCustomFile('include/order/class-wc-keycrm-order.php'));
            require_once(self::checkCustomFile('include/order/class-wc-keycrm-order-payment.php'));
            require_once(self::checkCustomFile('include/order/class-wc-keycrm-order-item.php'));
            require_once(self::checkCustomFile('include/order/class-wc-keycrm-order-address.php'));
            require_once(self::checkCustomFile('include/customer/class-wc-keycrm-customer-address.php'));
            require_once(self::checkCustomFile('include/customer/class-wc-keycrm-customer-corporate-address.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-icml.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-orders.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-customers.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-inventories.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-history.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-products.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-webhook-handler.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-base.php'));
            require_once(self::checkCustomFile('include/functions.php'));
            require_once(self::checkCustomFile('include/interfaces/class-keycrm-plugin-checker-interface.php'));
            require_once(self::checkCustomFile('include/abstracts/class-keycrm-abstract-shipping-plugin.php'));
            require_once(self::checkCustomFile('include/class-keycrm-shipping-manager.php'));
            require_once(self::checkCustomFile('include/shipping-plugins/mrkv-ua-shipping.php'));
            require_once(self::checkCustomFile('include/shipping-plugins/wc-ukr-shipping.php'));
            require_once(self::checkCustomFile('include/shipping-plugins/nova-poshta-ttn.php'));
            require_once(self::checkCustomFile('include/shipping-plugins/shipping-nova-poshta-for-woocommerce.php'));
            require_once(self::checkCustomFile('include/class-wc-keycrm-forms.php'));

        }

        /**
         * Check custom file
         *
         * @param string $file
         *
         * @return string
         */
        public static function checkCustomFile($file)
        {
            $wooPath = WP_PLUGIN_DIR . '/woo-keycrm/' . $file;
            $withoutInclude = WP_CONTENT_DIR . '/keycrm-custom/' . str_replace('include/', '', $file);

            if (file_exists($withoutInclude)) {
                return $withoutInclude;
            }

            if (file_exists($wooPath)) {
                return $wooPath;
            }

            return dirname(__FILE__) . '/' . $file;
        }

        /**
         * Returns true if WooCommerce was found in plugin cache
         *
         * @return bool
         */
        private function isWooCommerceInstalled()
        {
            $plugins = wp_cache_get( 'plugins', 'plugins' );

            if (!$plugins) {
                $plugins = get_plugins();
            } elseif (isset($plugins[''])) {
                $plugins = $plugins[''];
            }

            if (!isset($plugins[static::WOOCOMMERCE_PLUGIN_PATH])) {
                return false;
            }

            return true;
        }

        /**
         * Generate plugin installation url
         *
         * @param $pluginSlug
         *
         * @return string
         */
        private function generatePluginInstallationUrl($pluginSlug)
        {
            $action = 'install-plugin';

            return wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => $action,
                        'plugin' => $pluginSlug
                    ),
                    admin_url( 'update.php' )
                ),
                $action.'_'.$pluginSlug
            );
        }
    }

    if (!class_exists('WC_Keycrm_Plugin')) {
        require_once(WC_Integration_Keycrm::checkCustomFile('include/class-wc-keycrm-plugin.php'));
    }

    $plugin = WC_Keycrm_Plugin::getInstance(__FILE__);
    $plugin->register_activation_hook();
    $plugin->register_deactivation_hook();

    add_action('plugins_loaded', array('WC_Integration_Keycrm', 'get_instance'), 0);
endif;
