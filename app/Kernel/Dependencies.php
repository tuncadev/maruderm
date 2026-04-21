<?php

declare( strict_types=1 );

namespace Maruderm\Kernel;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Dependencies.
 *
 * Manages and checks external plugin dependencies required for the theme.
 * Projects can configure required plugins via the 'required_plugins' key in app/config.php.
 */
class Dependencies {

    /**
     * Base required plugins. Projects can override via app/config.php.
     *
     * @var array Array of required plugins.
     */
    private static array $base_required_plugins = [];

    /**
     * Minimum required PHP version.
     *
     * @var string Minimum required PHP version.
     */
    private static string $min_php_version = '8.4.0';

    /**
     * Checks if all required plugins are installed and active.
     *
     * @return bool True if all dependencies are met, false otherwise.
     */
    public static function check(): bool {
        $all_met = true;

        // Check PHP version
        if ( version_compare( PHP_VERSION, self::$min_php_version, '<' ) ) {
            $all_met = false;
            Application::get_instance()->render_error_notice( 'Maruderm theme requires PHP version ' . esc_html( self::$min_php_version ) . ' or higher.', 'Current version: ' . esc_html( PHP_VERSION ) );
        }

        // Allow projects to add/modify required plugins
        $required_plugins = apply_filters( 'maruderm/required_plugins', self::$base_required_plugins );

        foreach ( $required_plugins as $key => $plugin ) {

            // Check if the plugin is active
            if ( $key === 'advanced-custom-fields-pro' ) {

                // Because ACF Pro is a premium plugin, we check if it's active using a different method
                if ( ! self::is_acf_pro_active() ) {
                    $all_met = false;
                    Application::get_instance()->render_error_notice(
                        esc_html( $plugin['name'] ) . ' is required.',
                        'Install it from <a href="' . esc_url( $plugin['url'] ) . '">here</a>.'
                    );
                } elseif ( ! self::check_plugin_version( $plugin['slug'], $plugin['min_version'] ) ) {
                    $all_met = false;
                    Application::get_instance()->render_error_notice( esc_html( $plugin['name'] ) . ' requires version ' . esc_html( $plugin['min_version'] ) . ' or higher.' );
                }
            } elseif ( ! self::is_plugin_active( $plugin['slug'] ) ) {
                $all_met = false;
                Application::get_instance()->render_error_notice(
                    esc_html( $plugin['name'] ) . ' is required.',
                    'Install it from <a href="' . esc_html( $plugin['url'] ) . '">here</a>. '
                );
            } elseif ( ! self::check_plugin_version( $plugin['slug'], $plugin['min_version'] ) ) {
                $all_met = false;
                Application::get_instance()->render_error_notice( esc_html( $plugin['name'] ) . ' requires version ' . esc_html( $plugin['min_version'] ) . ' or higher.' );
            }
        }

        return $all_met;
    }

    /**
     * Checks if a plugin is active.
     *
     * @param string $plugin_slug The plugin's main file (e.g., 'wp-graphql/wp-graphql.php').
     *
     * @return bool True if the plugin is active, false otherwise.
     */
    private static function is_plugin_active( string $plugin_slug ): bool {

        // Get the list of active plugins as an array
        $active_plugins = (array) get_option( 'active_plugins', [] );

        // Check if the plugin is present in the list of active plugins
        return in_array( $plugin_slug, $active_plugins, true );
    }

    /**
     * Checks if ACF Pro is active by verifying the presence of a Pro-specific function.
     *
     * @return bool True if ACF Pro is active, false otherwise.
     */
    private static function is_acf_pro_active(): bool {
        return class_exists( 'ACF' );
    }

    /**
     * Checks if the plugin version meets the minimum requirement.
     *
     * @param string $plugin_slug The plugin's main file.
     * @param string $min_version Minimum required version.
     *
     * @return bool True if the plugin version is greater than or equal to the minimum version, false otherwise.
     */
    private static function check_plugin_version( string $plugin_slug, string $min_version ): bool {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_slug, false, false );
        $current_version = $plugin_data['Version'] ?? '0.0.0';

        return version_compare( $current_version, $min_version, '>=' );
    }
}
