<?php
# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

# Include nova post menu
require_once 'settings/mrkv-ua-shipping-settings.php'; 
# Include nova post menu
require_once 'shipping_methods/mrkv-ua-shipping-methods.php'; 
# Include woocommerce settings
require_once 'woocommerce/mrkv-ua-shipping-woocommerce.php'; 

# Check if class exist
if (!class_exists('MRKV_UA_SHIPPING_RUN'))
{
	/**
	 * Class for setup plugin 
	 */
	class MRKV_UA_SHIPPING_RUN
	{
		/**
		 * Constructor for plugin setup
		 * */
		function __construct()
		{
			# Setup woo plugin settings
			new MRKV_UA_SHIPPING_SETTINGS();

			# Setup woo plugin shipping methods
			new MRKV_UA_SHIPPING_METHODS();

			# Setup woo plugin woocommerce settings
			new MRKV_UA_SHIPPING_WOOCOMMERCE();
		}
	}
}