<?php

/**
 * Interface for shipping plugins integration with KeyCRM
 */
interface Keycrm_Shipping_Plugin_Interface {

    /**
     * Check if shipping plugin is active
     *
     * @return bool
     */
    public function is_active();

    /**
     * Get plugin name
     *
     * @return string
     */
    public function get_plugin_name();

    /**
     * Check if this plugin handles the shipping method
     *
     * @param string $method_id Shipping method ID
     * @return bool
     */
    public function handles_method($method_id);

    /**
     * Get shipping data from order
     *
     * @param WC_Order $order WooCommerce order object
     * @param WC_Order_Item_Shipping $shipping_method Shipping method item
     * @return array Shipping data with any of these fields:
     *               - delivery_service_id
     *               - tracking_code
     *               - shipping_service
     *               - shipping_address_city
     *               - shipping_address_country
     *               - shipping_address_region
     *               - shipping_address_zip
     *               - shipping_secondary_line
     *               - shipping_receive_point
     *               - recipient_full_name
     *               - recipient_phone
     *               - warehouse_ref
     *               - shipping_date
     */
    public function get_shipping_data($order, $shipping_method);
}