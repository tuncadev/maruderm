<?php
/**
 * Sends one internal notification for each WooCommerce checkout order.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_WooCommerce_Order_Notifier
{
    private const SENT_META = '_maruderm_internal_new_order_email_sent';
    private const CUSTOMER_SENT_META = '_maruderm_customer_order_received_email_sent';
    private const LOG_SOURCE = 'maruderm-transactional-email';

    private Maruderm_Order_Email_Renderer $renderer;
    private Maruderm_Customer_Order_Email_Renderer $customer_renderer;

    public function __construct(
        Maruderm_Order_Email_Renderer $renderer,
        Maruderm_Customer_Order_Email_Renderer $customer_renderer
    )
    {
        $this->renderer = $renderer;
        $this->customer_renderer = $customer_renderer;
    }

    public function register(): void
    {
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_on_hold_order', '__return_false');
        add_action('woocommerce_checkout_order_created', [$this, 'notify'], 30);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'notify'], 30);
    }

    public function notify($order): void
    {
        if (is_numeric($order)) {
            $order = wc_get_order((int) $order);
        }

        if (! $order instanceof WC_Order) {
            return;
        }

        $this->notify_internal($order);
        $this->send_customer_received($order);
    }

    public function send_customer_received(WC_Order $order): bool
    {
        if ($order->get_meta(self::CUSTOMER_SENT_META) !== '') {
            return true;
        }

        $recipient = sanitize_email($order->get_billing_email());

        if ($recipient === '') {
            $this->log('warning', 'Customer order email skipped because billing email is missing.', $order->get_id());
            return false;
        }

        $sent = wp_mail(
            $recipient,
            sprintf('Замовлення №%s отримано — MARUDERM', $order->get_order_number()),
            $this->customer_renderer->render($this->normalize($order)),
            ['Content-Type: text/html; charset=UTF-8']
        );

        if (! $sent) {
            $this->log('error', 'Customer order-received email failed.', $order->get_id());
            return false;
        }

        $order->update_meta_data(self::CUSTOMER_SENT_META, gmdate('c'));
        $order->save_meta_data();
        $this->log('notice', 'Customer order-received email sent.', $order->get_id());

        return true;
    }

    private function notify_internal(WC_Order $order): void
    {
        if ($order->get_meta(self::SENT_META) !== '') {
            return;
        }

        $recipient = $this->recipient();

        if ($recipient === '') {
            $this->log('error', 'Internal order recipient is not configured.', $order->get_id());
            return;
        }

        $sent = wp_mail(
            $recipient,
            sprintf('Нове замовлення №%s — сайт MARUDERM', $order->get_order_number()),
            $this->renderer->render($this->normalize($order)),
            ['Content-Type: text/html; charset=UTF-8']
        );

        if (! $sent) {
            $this->log('error', 'Internal WooCommerce order email failed.', $order->get_id());
            return;
        }

        $order->update_meta_data(self::SENT_META, gmdate('c'));
        $order->save_meta_data();
        $this->log('notice', 'Internal WooCommerce order email sent.', $order->get_id());
    }

    public function normalize(WC_Order $order): array
    {
        $items = [];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $image_id = $product instanceof WC_Product ? $product->get_image_id() : 0;
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => wp_strip_all_tags(wc_price($order->get_line_total($item), ['currency' => $order->get_currency()])),
                'image' => $image_id > 0 ? (string) wp_get_attachment_image_url($image_id, 'thumbnail') : '',
            ];
        }

        return [
            'order_number' => '#' . $order->get_order_number(),
            'date' => wp_date('j F Y, H:i', $order->get_date_created()?->getTimestamp() ?: time()),
            'source' => 'maruderm.com.ua',
            'customer_name' => trim($order->get_formatted_billing_full_name()),
            'customer_phone' => $order->get_billing_phone(),
            'customer_email' => $order->get_billing_email(),
            'items' => $items,
            'subtotal' => wp_strip_all_tags(wc_price($order->get_subtotal(), ['currency' => $order->get_currency()])),
            'shipping_total' => (float) $order->get_shipping_total() > 0
                ? wp_strip_all_tags(wc_price($order->get_shipping_total(), ['currency' => $order->get_currency()]))
                : 'Безкоштовно',
            'total' => wp_strip_all_tags($order->get_formatted_order_total()),
            'delivery' => $order->get_shipping_method() ?: 'Не вказано',
            'payment' => $order->get_payment_method_title() ?: 'Не вказано',
            'order_url' => $order->get_edit_order_url(),
            'customer_order_url' => $order->get_checkout_order_received_url(),
            'shipping_address' => wp_strip_all_tags($order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address()),
        ];
    }

    private function recipient(): string
    {
        $email = defined('MARUDERM_ORDER_NOTIFICATION_EMAIL')
            ? (string) constant('MARUDERM_ORDER_NOTIFICATION_EMAIL')
            : 'zakaz@maruderm.com.ua';

        return sanitize_email($email);
    }

    private function log(string $level, string $message, int $order_id): void
    {
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->log($level, $message, ['source' => self::LOG_SOURCE, 'order_id' => $order_id]);
        }
    }
}
