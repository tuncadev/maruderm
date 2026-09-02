<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shipping plugins manager for KeyCRM
 */
class Keycrm_Shipping_Manager {

    private static $instance;
    private $plugins = array();
    private $plugins_loaded = false;
    private $active_plugins = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Lazy loading - plugins will be loaded when needed
    }

    private function load_plugins() {
        if ($this->plugins_loaded) {
            return;
        }

        do_action('keycrm_register_shipping_plugins', $this);
        $this->plugins_loaded = true;

        $this->active_plugins = array_filter($this->plugins, function($plugin) {
            return $plugin->is_active();
        });
    }

    public function register_plugin(Keycrm_Shipping_Plugin_Interface $plugin) {
        $this->plugins[] = $plugin;
    }

    /**
     * Get shipping data from order
     *
     * @param WC_Order $order Order object
     * @return array Shipping data
     */
    public function get_shipping_data($order) {
        $shipping_methods = $order->get_shipping_methods();
        if (empty($shipping_methods)) {
            return array();
        }

        $shipping_method = reset($shipping_methods);
        $method_id = $shipping_method->get_method_id();

        $this->load_plugins();

        foreach ($this->active_plugins as $plugin) {
            if ($plugin->handles_method($method_id)) {
                return $plugin->get_shipping_data($order, $shipping_method);
            }
        }

        return array();
    }
}