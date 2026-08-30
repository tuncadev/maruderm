<?php
/**
 * Keeps WPGraphQL and WPGraphQL WooCommerce active for the headless
 * frontend. The project's daily production-to-local database sync
 * (.agents/skills/sync-maruderm-production-db) overwrites the
 * `active_plugins` option with production's list, which deactivates these
 * two since they are only installed locally for the maruderm.next headless
 * build. Mu-plugins are file-based and unaffected by that sync, so this
 * re-activates them on load instead of requiring a manual `wp plugin
 * activate` after every sync.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'maruderm_ensure_headless_plugins_active');
add_action('init', 'maruderm_ensure_headless_plugins_active', 0);

function maruderm_ensure_headless_plugins_active(): void
{
    if (! function_exists('is_plugin_active') || ! function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    foreach (['wp-graphql/wp-graphql.php', 'wp-graphql-woocommerce/wp-graphql-woocommerce.php'] as $plugin) {
        if (! is_plugin_active($plugin) && file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
            activate_plugin($plugin);
        }
    }
}
