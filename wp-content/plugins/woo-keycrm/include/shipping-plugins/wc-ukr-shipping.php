<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC Ukraine Shipping plugin integration for KeyCRM
 */
class Keycrm_WC_Ukr_Shipping_Plugin extends Keycrm_Abstract_Shipping_Plugin {

    public function __construct() {
        parent::__construct(
            'WC Ukraine Shipping',
            'wc-ukr-shipping/wc-ukr-shipping.php',
            array('wcus_', 'nova_poshta_shipping')
        );
    }

    /**
     * Check if plugin main classes are loaded
     *
     * @return bool
     */
    protected function is_loaded() {
        return class_exists('kirillbdev\WCUkrShipping\Foundation\WCUkrShipping');
    }

    /**
     * Get shipping data from order
     *
     * @param WC_Order $order WooCommerce order object
     * @param WC_Order_Item_Shipping $shipping_method Shipping method item
     * @return array Shipping data
     */
    public function get_shipping_data($order, $shipping_method) {
        $method_id = $shipping_method->get_method_id();
        $shipping_data = array();

        $method_data_map = array(
            'nova_poshta_shipping' => array(
                'warehouse_ref' => 'wcus_warehouse_ref'
            )
        );

        if (isset($method_data_map[$method_id])) {
            $method_data = $method_data_map[$method_id];

            $warehouse_ref = $shipping_method->get_meta($method_data['warehouse_ref']);
            if (!empty($warehouse_ref)) {
                $shipping_data['warehouse_ref'] = $warehouse_ref;
            }
        }

        return $shipping_data;
    }
}

add_action('keycrm_register_shipping_plugins', function($manager) {
    $manager->register_plugin(new Keycrm_WC_Ukr_Shipping_Plugin());
});