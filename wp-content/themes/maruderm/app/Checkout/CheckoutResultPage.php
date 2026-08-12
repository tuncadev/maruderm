<?php

declare(strict_types=1);

namespace Maruderm\Checkout;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class CheckoutResultPage implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_filter('template_include', [$this, 'resolveTemplate'], 60);
        add_action('wp', [$this, 'removeInheritedHeader'], 20);
    }

    public function resolveTemplate(string $template): string
    {
        $order = self::currentOrder();

        if (!$order instanceof \WC_Order || $order->has_status('failed')) {
            return $template;
        }

        $template_name = self::isBankTransferView()
            ? 'woocommerce/bank-transfer-page.php'
            : 'woocommerce/thank-you-page.php';
        $result_template = get_theme_file_path($template_name);

        return file_exists($result_template) ? $result_template : $template;
    }

    public function removeInheritedHeader(): void
    {
        if (!self::currentOrder() instanceof \WC_Order) {
            return;
        }

        remove_action('martfury_after_header', 'martfury_page_header');
        remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
        remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
    }

    public static function isBankTransferView(): bool
    {
        $order = self::currentOrder();
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';

        return $order instanceof \WC_Order
            && $order->get_payment_method() === 'bacs'
            && $view !== 'thanks';
    }

    public static function currentOrder(): ?\WC_Order
    {
        if (!function_exists('is_order_received_page') || !is_order_received_page()) {
            return null;
        }

        $order_id = absint(get_query_var('order-received'));
        $order_key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        $order = $order_id > 0 ? wc_get_order($order_id) : false;

        if (!$order instanceof \WC_Order || $order_key === '' || !hash_equals($order->get_order_key(), $order_key)) {
            return null;
        }

        return $order;
    }
}
