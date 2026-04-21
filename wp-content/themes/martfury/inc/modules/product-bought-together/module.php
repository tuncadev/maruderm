<?php
/**
 * Martfury Modules functions and definitions.
 *
 * @package Martfury
 */

namespace Martfury\Modules\Product_Bought_Together;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Modules
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
		add_action('init', array( $this, 'actions' ));
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
			'Martfury\Modules\Product_Bought_Together\Frontend'     => get_template_directory() . '/inc/modules/product-bought-together/frontend.php',
			'Martfury\Modules\Product_Bought_Together\Product_Meta' => get_template_directory() . '/inc/modules/product-bought-together/product-meta.php',
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
		if ( intval( martfury_get_option( 'product_fbt' ) ) ) {
			\Martfury\Modules\Product_Bought_Together\Frontend::instance();

			if ( is_admin() ) {
				\Martfury\Modules\Product_Bought_Together\Product_Meta::instance();
			}
		}

	}

}
