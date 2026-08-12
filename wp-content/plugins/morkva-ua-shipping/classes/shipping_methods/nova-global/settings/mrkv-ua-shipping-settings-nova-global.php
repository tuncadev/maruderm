<?php
# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

# Check if class exist
if (!class_exists('MRKV_UA_SHIPPING_SETTINGS_NOVA_GLOBAL'))
{
	/**
	 * Class for setup shipping methods settings
	 */
	class MRKV_UA_SHIPPING_SETTINGS_NOVA_GLOBAL
	{
		/**
		 * Constructor for plugin shipping methods settings
		 * */
		function __construct()
		{
			$this->create_tabs_data();
		}

		private function create_tabs_data()
		{
			define('MRKV_OPTION_TABS', array(
				'basic_settings' => __('Basic', 'mrkv-ua-shipping'),
				'default_settings' => __('Default values', 'mrkv-ua-shipping'),
				'checkout_settings' => __('Checkout', 'mrkv-ua-shipping'),
				'log_settings' => __('Log', 'mrkv-ua-shipping')
			));
		}
	}
}