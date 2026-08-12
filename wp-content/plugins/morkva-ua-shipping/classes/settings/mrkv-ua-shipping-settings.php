<?php
# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

# Include ua shipping options
require_once 'global/mrkv-ua-shipping-options.php'; 
# Include ua shipping menu
require_once 'admin/mrkv-ua-shipping-menu.php'; 
# Include settings assets
require_once 'admin/mrkv-ua-shipping-admin-assets.php';
# Include debug log
require_once 'log/mrkv-ua-shipping-log.php'; 
# Include notification
require_once 'admin/mrkv-ua-shipping-notification.php'; 

# Check if class exist
if (!class_exists('MRKV_UA_SHIPPING_SETTINGS'))
{
	/**
	 * Class for setup plugin settings
	 */
	class MRKV_UA_SHIPPING_SETTINGS
	{
		/**
		 * Constructor for plugin settings
		 * */
		function __construct()
		{
			# Setup woo plugin settings options
			new MRKV_UA_SHIPPING_OPTIONS();

			# Setup woo plugin settings menu
			new MRKV_UA_SHIPPING_MENU();

			# Setup woo plugin settings assets
			new MRKV_UA_SHIPPING_ADMIN_ASSETS();

			# Setup woo plugin settings notification
			new MRKV_UA_SHIPPING_NOTIFICATION();
		}
	}
}