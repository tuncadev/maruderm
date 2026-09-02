<?php

/**
 * Abstract base class for shipping plugins
 */
abstract class Keycrm_Abstract_Shipping_Plugin implements Keycrm_Shipping_Plugin_Interface {

    protected $plugin_name;
    protected $plugin_file;
    protected $method_patterns = array();

    /**
     * Constructor
     *
     * @param string $plugin_name Plugin name
     * @param string $plugin_file Plugin main file
     * @param array $method_patterns Method ID patterns
     */
    public function __construct($plugin_name, $plugin_file, $method_patterns = array()) {
        $this->plugin_name = $plugin_name;
        $this->plugin_file = $plugin_file;
        $this->method_patterns = $method_patterns;
    }

    /**
     * Check if shipping plugin is active
     *
     * @return bool
     */
    public function is_active() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        return is_plugin_active($this->plugin_file) || $this->is_loaded();
    }

    /**
     * Get plugin name
     *
     * @return string
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Check if this plugin handles the shipping method
     *
     * @param string $method_id Shipping method ID
     * @return bool
     */
    public function handles_method($method_id) {
        foreach ($this->method_patterns as $pattern) {
            if (strpos($method_id, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get meta value from order with multiple key options
     *
     * @param WC_Order $order Order object
     * @param array $meta_keys Array of meta keys to try
     * @return string|null
     */
    protected function get_meta_value($order, $meta_keys) {
        foreach ($meta_keys as $meta_key) {
            $value = $order->get_meta($meta_key);
            if (!empty($value)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Check if plugin main classes are loaded
     *
     * @return bool
     */
    abstract protected function is_loaded();

    /**
     * Get shipping data from order
     *
     * @param WC_Order $order WooCommerce order object
     * @param WC_Order_Item_Shipping $shipping_method Shipping method item
     * @return array Shipping data
     */
    abstract public function get_shipping_data($order, $shipping_method);
}