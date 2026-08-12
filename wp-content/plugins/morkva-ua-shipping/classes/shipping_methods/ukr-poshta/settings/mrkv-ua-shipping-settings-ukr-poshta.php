<?php
# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

# Check if class exist
if (!class_exists('MRKV_UA_SHIPPING_SETTINGS_UKR_POSHTA'))
{
	/**
	 * Class for setup shipping methods settings
	 */
	class MRKV_UA_SHIPPING_SETTINGS_UKR_POSHTA
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
				'domestic_settings' => __('Domestic', 'mrkv-ua-shipping'),
				'international_settings' => __('International', 'mrkv-ua-shipping'),
				'email_settings' => __('Email', 'mrkv-ua-shipping'),
				'automation_settings' => __('Automation', 'mrkv-ua-shipping'),
				'checkout_settings' => __('Checkout', 'mrkv-ua-shipping'),
				'log_settings' => __('Log', 'mrkv-ua-shipping')
			));
		}
	}
}