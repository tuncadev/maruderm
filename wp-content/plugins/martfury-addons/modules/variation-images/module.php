<?php
/**
 * Martfury Addons Modules functions and definitions.
 *
 * @package Martfury
 */

namespace Martfury\Addons\Modules\Variation_Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Addons Modules
 */
class Module {

	/**
	 * Instance
	 *
	 * @var $instance
	 */
	private static $instance;


	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Instantiate the object.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Includes files
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function includes() {
		\Martfury\Addons\Auto_Loader::register( [
			'Martfury\Addons\Modules\Variation_Images\Frontend'        => MARTFURY_ADDONS_DIR . 'modules/variation-images/frontend.php',
			'Martfury\Addons\Modules\Variation_Images\Settings'    	   => MARTFURY_ADDONS_DIR . 'modules/variation-images/settings.php',
			'Martfury\Addons\Modules\Variation_Images\Product_Options' => MARTFURY_ADDONS_DIR . 'modules/variation-images/product-options.php',
		] );
	}


	/**
	 * Add Actions
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function actions() {
		if ( is_admin() ) {
			\Martfury\Addons\Modules\Variation_Images\Settings::instance();

			if ( get_option( 'martfury_variation_images' ) == 'yes' ) {
				\Martfury\Addons\Modules\Variation_Images\Product_Options::instance();
			}
		}

		if ( get_option( 'martfury_variation_images' ) == 'yes' ) {
			\Martfury\Addons\Modules\Variation_Images\Frontend::instance();
		}

	}

}
