<?php

namespace Martfury\Addons\Elementor;

use Martfury\Addons\Elementor\Controls\AjaxLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Controls {

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
		// Include plugin files
		$this->includes();

		// Register controls
		add_action( 'elementor/controls/register', [ $this, 'register_controls' ] );

		AjaxLoader::instance();
	}

	/**
	 * Include Files
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function includes() {
		\Martfury\Addons\Auto_Loader::register( [
				'Martfury\Addons\Elementor\Controls\AjaxLoader'  => MARTFURY_ADDONS_DIR . 'inc/controls/ajaxloader.php',
				'Martfury\Addons\Elementor\Control\Autocomplete' => MARTFURY_ADDONS_DIR . 'inc/controls/autocomplete.php',
			]
		);

	}

	/**
	 * Register autocomplete control
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_controls() {
		$controls_manager = \Elementor\Plugin::$instance->controls_manager;
		$controls_manager->register( new \Martfury\Addons\Elementor\Control\Autocomplete() );

	}
}