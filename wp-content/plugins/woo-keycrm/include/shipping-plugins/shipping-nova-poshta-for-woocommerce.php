<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shipping Nova Poshta for WooCommerce plugin integration for KeyCRM
 */
class Keycrm_Shipping_Nova_Poshta_For_Woocommerce_Plugin extends Keycrm_Abstract_Shipping_Plugin {

    public function __construct() {
        parent::__construct(
            'Shipping Nova Poshta for WooCommerce',
            'shipping-nova-poshta-for-woocommerce/shipping-nova-poshta-for-woocommerce.php',
            array('shipping_nova_poshta_for_woocommerce')
        );
    }

    /**
     * Check if plugin main classes are loaded
     *
     * @return bool
     */
    protected function is_loaded() {
        return class_exists('NovaPoshta\Main') || function_exists('nova_poshta');
    }

    /**
     * Get shipping data from order
     *
     * @param WC_Order $order WooCommerce order object
     * @param WC_Order_Item_Shipping $shipping_method Shipping method item
     * @return array Shipping data
     */
    public function get_shipping_data($order, $shipping_method) {
        $shipping_data = array();
        $warehouse_ref = $order->get_meta('_shipping_nova_poshta_for_woocommerce_warehouse');
        if (!empty($warehouse_ref)) {
            $shipping_data['warehouse_ref'] = $warehouse_ref;
        }

        return $shipping_data;
    }
}

add_action('keycrm_register_shipping_plugins', function($manager) {
    $manager->register_plugin(new Keycrm_Shipping_Nova_Poshta_For_Woocommerce_Plugin());
});