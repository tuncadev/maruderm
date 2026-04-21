<?php
/**
 * Theme entry point.
 *
 * @package Maruderm
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/app/Bootstrap.php';
add_filter('woocommerce_attribute_show_in_nav_menus', '__return_true');

add_action('wp_enqueue_scripts', static function (): void {
    $isAuthTemplate = is_page_template('template-login-register.php') || is_page('login');
    $isMyAccount = function_exists('is_account_page') && is_account_page();

    if (! $isAuthTemplate && ! $isMyAccount) {
        return;
    }

    $stylePath = get_theme_file_path('assets/auth/auth-forms.css');
    $scriptPath = get_theme_file_path('assets/auth/auth-forms.js');

    wp_enqueue_style(
        'maruderm-auth-forms',
        get_theme_file_uri('assets/auth/auth-forms.css'),
        [],
        file_exists($stylePath) ? (string) filemtime($stylePath) : null
    );

    wp_enqueue_script(
        'maruderm-auth-forms',
        get_theme_file_uri('assets/auth/auth-forms.js'),
        [],
        file_exists($scriptPath) ? (string) filemtime($scriptPath) : null,
        true
    );
}, 40);
