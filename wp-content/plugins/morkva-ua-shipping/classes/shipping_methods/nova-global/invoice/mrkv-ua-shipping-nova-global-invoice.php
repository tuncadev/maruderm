<?php
# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

# Check if class exist
if (!class_exists('MRKV_UA_SHIPPING_NOVA_GLOBAL_INVOICE'))
{
	/**
	 * Class for setup shipping methods invoice create
	 */
	class MRKV_UA_SHIPPING_NOVA_GLOBAL_INVOICE
	{
		/**
		 * Constructor for plugin shipping methods invoice create
		 * */
		function __construct($order, $post_fields, $shipping_api, $settings_shipping)
		{

		}
	}
}