<?php

declare(strict_types=1);

namespace Maruderm\Cart;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class CartPage implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_filter('template_include', [$this, 'resolveTemplate'], 50);
        add_action('wp', [$this, 'removeInheritedHeader'], 20);
    }

    public function resolveTemplate(string $template): string
    {
        if (!function_exists('is_cart') || !is_cart()) {
            return $template;
        }

        $cart_template = get_theme_file_path('woocommerce/cart-page.php');

        return file_exists($cart_template) ? $cart_template : $template;
    }

    public function removeInheritedHeader(): void
    {
        if (function_exists('is_cart') && is_cart()) {
            remove_action('martfury_after_header', 'martfury_page_header');
            remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
            remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
        }
    }
}
