<?php
/**
 * Plugin Name: Martfury Addons
 * Plugin URI: http://drfuri.com/martfury
 * Description: Extra elements for WPBakery Page Builder and Elementor. It was built for Martfury theme.
 * Version: 3.3.9
 * Author: DrFuri
 * Author URI: http://drfuri.com/
 * License: GPL2+
 * Text Domain: martfury-addons
 * Domain Path: lang/
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! defined( 'MARTFURY_ADDONS_DIR' ) ) {
	define( 'MARTFURY_ADDONS_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MARTFURY_ADDONS_URL' ) ) {
	define( 'MARTFURY_ADDONS_URL', plugin_dir_url( __FILE__ ) );
}

require_once MARTFURY_ADDONS_DIR . '/inc/taxonomies.php';
require_once MARTFURY_ADDONS_DIR . '/inc/visual-composer.php';
require_once MARTFURY_ADDONS_DIR . '/inc/shortcodes.php';
require_once MARTFURY_ADDONS_DIR . '/inc/functions.php';
require_once MARTFURY_ADDONS_DIR . '/inc/widgets/widgets.php';

if ( is_admin() ) {
	require_once MARTFURY_ADDONS_DIR . '/inc/importer.php';
}

/**
 * Init
 */
function martfury_vc_addons_init() {
	new Martfury_Taxonomies;
	new Martfury_VC;
	new Martfury_Shortcodes;

}

add_action( 'after_setup_theme', 'martfury_vc_addons_init', 30 );

function martfury_unregister_sidebar() {
	unregister_widget( 'WC_Widget_Layered_Nav' );
	unregister_widget( 'WC_Widget_Layered_Nav_Filters' );
	unregister_widget( 'WC_Widget_Rating_Filter' );
}

add_action( 'widgets_init', 'martfury_unregister_sidebar' );

/**
 * Undocumented function
 */
function martfury_init_elementor() {
	martfury_includes();
	spl_autoload_register( '\Martfury\Addons\Auto_Loader::load' );
	martfury_add_actions();

	// Check if Elementor installed and activated
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	// Check for required Elementor version
	if ( ! version_compare( ELEMENTOR_VERSION, '2.0.0', '>=' ) ) {
		return;
	}

	// Check for required PHP version
	if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
		return;
	}

	// Once we get here, We have passed all validation checks so we can safely include our plugin
	include_once( MARTFURY_ADDONS_DIR . 'inc/elementor-autoloader.php' );
	include_once( MARTFURY_ADDONS_DIR . 'inc/elementor.php' );
	include_once( MARTFURY_ADDONS_DIR . 'inc/elementor-ajaxloader.php' );
}

add_action( 'plugins_loaded', 'martfury_init_elementor' );

/**
 * Includes files
 *
 * @since 1.0.0
 *
 * @return void
 */
function martfury_includes() {
	// Auto Loader
	require_once MARTFURY_ADDONS_DIR . 'inc/elementor-autoloader.php';
	\Martfury\Addons\Auto_Loader::register( [
		'Martfury\Addons\Modules'        => MARTFURY_ADDONS_DIR . 'modules/modules.php',
	] );
}

/**
 * Add Actions
 *
 * @since 1.0.0
 *
 * @return void
 */
function martfury_add_actions() {
	// Before init action.
	do_action( 'before_martfury_init' );

	// Modules
	martfury_get( 'modules' );

	// Init action.
	do_action( 'after_martfury_init' );
}

/**
 * Get Martfury Addons Class instance
 *
 * @since 1.0.0
 *
 * @return object
 */
function martfury_get( $class ) {
	switch ( $class ) {
		case 'modules':
			return \Martfury\Addons\Modules::instance();
			break;
	}
}