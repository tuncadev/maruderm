<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shipping for Nova Poshta plugin integration for KeyCRM
 */
class Keycrm_Nova_Poshta_Shipping_Ttn_Plugin extends Keycrm_Abstract_Shipping_Plugin {

    public function __construct() {
        parent::__construct(
            'Shipping for Nova Poshta',
            'nova-poshta-ttn/nova-poshta-ttn.php',
            array('nova_poshta_shipping_method')
        );
    }

    /**
     * Check if plugin main classes are loaded
     *
     * @return bool
     */
    protected function is_loaded() {
        return class_exists('NovattnPoshta') || class_exists('MNP_Plugin');
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

        // Map method IDs to meta keys
        $method_data_map = array(
            'nova_poshta_shipping_method' => array(
                'warehouse_ref' => '_shipping_nova_poshta_warehouse'
            ),
            'nova_poshta_shipping_method_poshtomat' => array(
                'warehouse_ref' => '_shipping_nova_poshta_warehouse'
            )
        );
        // Get data for current method
        if (isset($method_data_map[$method_id])) {
            $method_data = $method_data_map[$method_id];

            // Get warehouse reference
            $warehouse_ref = $order->get_meta($method_data['warehouse_ref']);
            if (!empty($warehouse_ref)) {
                $shipping_data['warehouse_ref'] = $warehouse_ref;
            }



            // Try to get tracking number if available
            $tracking_code = $order->get_meta('_shipping_nova_poshta_ttn');
            if (!empty($tracking_code)) {
                $shipping_data['tracking_code'] = $tracking_code;
            }
        }

        return $shipping_data;
    }
}
add_action('keycrm_register_shipping_plugins', function($manager) {
    $manager->register_plugin(new Keycrm_Nova_Poshta_Shipping_Ttn_Plugin());
});