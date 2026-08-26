<?php
/**
 * Product pricing plugin bootstrap.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Product_Pricing_Plugin
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        if (! class_exists('WooCommerce') || ! class_exists('WC_Product')) {
            if (is_admin()) {
                add_action('admin_notices', [self::class, 'render_missing_woocommerce_notice']);
            }
            return;
        }

        $policy = new Maruderm_Product_Pricing_Policy();
        $repository = new Maruderm_Product_Price_Repository();

        (new Maruderm_Product_Pricing_Editor($policy, $repository))->register();
        (new Maruderm_Product_Pricing_Settings_Page($policy, $repository))->register();
        (new Maruderm_Out_Of_Stock_Pricing())->register();
    }

    public static function render_missing_woocommerce_notice(): void
    {
        echo '<div class="notice notice-error"><p>Maruderm Product Price Controls requires WooCommerce.</p></div>';
    }
}
