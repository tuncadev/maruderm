<?php
/**
 * Martfury Modules functions and definitions.
 *
 * @package Martfury
 */

namespace Martfury;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Modules
 */
class Modules {

	/**
	 * Instance
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Registered modules.
	 *
	 * Holds the list of all the registered modules.
	 *
	 * @var array
	 */
	private $modules = [];

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
		if( ! class_exists('\Martfury\Addons\Auto_Loader') ) {
			return;
		}
		$this->includes();
		$this->add_actions();

		add_action( 'init', [ $this, 'activate' ] );
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
			// Product Bought Together
			'Martfury\Modules\Product_Bought_Together\Module'    => get_template_directory() . '/inc/modules/product-bought-together/module.php',
		] );
	}


	/**
	 * Add Actions
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function add_actions() {
		\Martfury\Modules\Product_Bought_Together\Module::instance();
	}

	/**
	 * Register a module
	 *
	 * @param string $module_name
	 */
	public function register( $module_name ) {
		if ( ! array_key_exists( $module_name, $this->modules ) ) {
			$this->modules[ $module_name ] = null;
		}
	}

	/**
	 * Deregister a moudle.
	 * Only allow deregistering a module if it is not activated.
	 *
	 * @param string $module_name
	 */
	public function deregister( $module_name ) {
		if ( ! array_key_exists( $module_name, $this->modules ) && empty( $this->modules[ $module_name ] ) ) {
			unset( $this->modules[ $module_name ] );
		}
	}

	/**
	 * Active all registered modules
	 *
	 * @return void
	 */
	public function activate() {
		foreach ( $this->modules as $module_name => $instance ) {
			if ( ! empty( $instance ) ) {
				continue;
			}

			$classname = $this->get_module_classname( $module_name );

			if ( $classname ) {
				$this->modules[ $module_name ] = $classname::instance();
			}
		}
	}

	/**
	 * Get module class name
	 *
	 * @param string $module_name
	 * @return string
	 */
	public function get_module_classname( $module_name ) {
		$class_name = str_replace( '-', ' ', $module_name );
		$class_name = str_replace( ' ', '_', ucwords( $class_name ) );
		$class_name = 'Martfury\\Modules\\' . $class_name . '\\Module';

		return $class_name;
	}
}
