<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns page-level integration required by the custom single-product template. */
final class SingleProductPage implements Registrable
{
    use Loadable;

    private const LEGACY_FOOTER_METHODS = [
        'product_instagram_photos',
        'products_upsell_display',
        'related_products_output',
    ];

    public function register(): void
    {
        add_action('wp', [$this, 'removeLegacyFooterSections'], 99);
    }

    public function removeLegacyFooterSections(): void
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        global $wp_filter;

        if (empty($wp_filter['martfury_before_footer']) || !$wp_filter['martfury_before_footer'] instanceof \WP_Hook) {
            return;
        }

        foreach ($wp_filter['martfury_before_footer']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;

                if (!is_array($function) || !in_array($function[1] ?? null, self::LEGACY_FOOTER_METHODS, true)) {
                    continue;
                }

                remove_action('martfury_before_footer', $function, (int) $priority);
            }
        }
    }
}
