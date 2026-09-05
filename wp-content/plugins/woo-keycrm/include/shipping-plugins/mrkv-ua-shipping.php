<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Morkva UA Shipping plugin integration for KeyCRM
 */
class Keycrm_Morkva_Shipping_Plugin extends Keycrm_Abstract_Shipping_Plugin {

    public function __construct() {
        parent::__construct(
            'Morkva UA Shipping',
            'mrkv-ua-shipping/mrkv-ua-shipping.php',
            array('mrkv_ua_shipping_')
        );
    }

    /**
     * Check if plugin main classes are loaded
     *
     * @return bool
     */
    protected function is_loaded() {
        return class_exists('MRKV_UA_SHIPPING_RUN');
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
        var_dump($method_id);
        $shipping_data = array();

        // Map method IDs to meta keys
        $method_data_map = array(
            'mrkv_ua_shipping_nova-poshta' => array(
                'warehouse_ref' => 'mrkv_ua_shipping_nova-poshta_warehouse_ref'
            ),
            'mrkv_ua_shipping_nova-poshta_poshtamat' => array(
                'warehouse_ref' => 'mrkv_ua_shipping_nova-poshta_poshtamat_ref'
            )
        );

        if (isset($method_data_map[$method_id])) {
            $method_data = $method_data_map[$method_id];

            foreach ($method_data as $key => $meta_key) {
                $meta_value = $order->get_meta($meta_key);
                if (!empty($meta_value)) {
                    $shipping_data[$key] = $meta_value;
                }
            }
        }

        return $shipping_data;
    }
}

add_action('keycrm_register_shipping_plugins', function($manager) {
    $manager->register_plugin(new Keycrm_Morkva_Shipping_Plugin());
});