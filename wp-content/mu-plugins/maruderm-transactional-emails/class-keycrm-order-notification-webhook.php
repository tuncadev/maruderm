<?php
/**
 * Receives selected KeyCRM order events and sends deduplicated internal email.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_KeyCRM_Order_Notification_Webhook
{
    private const API_BASE_URL = 'https://openapi.keycrm.app/v1';
    private const ROUTE_NAMESPACE = 'maruderm-email/v1';
    private const ROUTE = '/keycrm-order';
    private const SETTINGS_OPTION = 'woocommerce_integration-keycrm_settings';
    private const ALLOWED_SOURCE_IDS = [1, 4, 5];
    private const NEW_STATUS_ID = 1;
    private const LOG_SOURCE = 'maruderm-transactional-email';

    private Maruderm_Order_Email_Renderer $renderer;

    public function __construct(Maruderm_Order_Email_Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_route']);
    }

    public function register_route(): void
    {
        register_rest_route(self::ROUTE_NAMESPACE, self::ROUTE, [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handle'],
            'permission_callback' => [$this, 'authorize'],
        ]);
    }

    public function authorize(WP_REST_Request $request)
    {
        $settings = $this->settings();
        $expected = trim((string) ($settings['webhook_secret_key'] ?? ''));
        $provided = trim((string) $request->get_header('X-KeyCRM-Webhook-Secret'));

        if ($provided === '') {
            $provided = trim((string) $request->get_param('secret'));
        }

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return new WP_Error('maruderm_email_unauthorized', 'Invalid webhook secret.', ['status' => 401]);
        }

        return true;
    }

    public function handle(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        $context = is_array($payload) && isset($payload['context']) && is_array($payload['context'])
            ? $payload['context']
            : [];
        $remote_id = absint($context['id'] ?? ($context['order_id'] ?? 0));

        if ($remote_id <= 0) {
            return new WP_Error('maruderm_email_invalid_order', 'KeyCRM order ID is missing.', ['status' => 422]);
        }

        $order = $this->remote_order($remote_id);

        if (is_wp_error($order)) {
            return $order;
        }

        $source_id = absint($order['source_id'] ?? ($order['source']['id'] ?? 0));
        $status_id = absint($order['status_id'] ?? ($order['status']['id'] ?? 0));

        if (! in_array($source_id, self::ALLOWED_SOURCE_IDS, true) || $status_id !== self::NEW_STATUS_ID) {
            return new WP_REST_Response(['ok' => true, 'result' => 'ignored'], 200);
        }

        $dedupe_key = 'maruderm_keycrm_internal_email_' . $remote_id;

        if (! add_option($dedupe_key, 'pending', '', false)) {
            return new WP_REST_Response(['ok' => true, 'result' => 'duplicate'], 200);
        }

        $sent = wp_mail(
            $this->recipient(),
            sprintf('Нове замовлення №%s — %s', (string) ($order['source_uuid'] ?? $remote_id), $this->source_name($order)),
            $this->renderer->render($this->normalize($order)),
            ['Content-Type: text/html; charset=UTF-8']
        );

        if (! $sent) {
            delete_option($dedupe_key);
            $this->log('error', 'Internal KeyCRM order email failed.', $remote_id);
            return new WP_Error('maruderm_email_send_failed', 'Email delivery failed.', ['status' => 502]);
        }

        update_option($dedupe_key, gmdate('c'), false);
        $this->log('notice', 'Internal KeyCRM order email sent.', $remote_id);

        return new WP_REST_Response(['ok' => true, 'result' => 'sent'], 200);
    }

    private function remote_order(int $remote_id)
    {
        $token = trim((string) ($this->settings()['api_key'] ?? ''));

        if ($token === '') {
            return new WP_Error('maruderm_email_keycrm_missing', 'KeyCRM API token is unavailable.', ['status' => 503]);
        }

        $response = wp_remote_get(
            self::API_BASE_URL . '/order/' . $remote_id . '?include=buyer,products.offer,delivery,payments,status,source',
            ['timeout' => 15, 'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']]
        );

        if (is_wp_error($response)) {
            return new WP_Error('maruderm_email_keycrm_failed', 'KeyCRM order lookup failed.', ['status' => 502]);
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) !== 200 || ! is_array($body)) {
            return new WP_Error('maruderm_email_keycrm_invalid', 'KeyCRM returned an invalid order.', ['status' => 502]);
        }

        return $body;
    }

    private function normalize(array $order): array
    {
        $currency = (string) ($order['currency'] ?? 'UAH');
        $items = [];

        foreach ((array) ($order['products'] ?? []) as $product) {
            if (! is_array($product)) {
                continue;
            }
            $quantity = (float) ($product['quantity'] ?? 1);
            $price = (float) ($product['price_sold'] ?? ($product['price'] ?? 0));
            $items[] = [
                'name' => (string) ($product['name'] ?? ($product['product_name'] ?? 'Товар')),
                'quantity' => $quantity,
                'price' => $this->money($price * $quantity, $currency),
                'image' => (string) ($product['picture'] ?? ($product['offer']['thumbnail_url'] ?? '')),
            ];
        }

        $buyer = is_array($order['buyer'] ?? null) ? $order['buyer'] : [];
        $delivery = is_array($order['delivery'] ?? null) ? $order['delivery'] : [];
        $payment = is_array($order['payments'][0] ?? null) ? $order['payments'][0] : [];

        return [
            'order_number' => '#' . (string) ($order['source_uuid'] ?? $order['id'] ?? ''),
            'date' => (string) ($order['ordered_at'] ?? $order['created_at'] ?? ''),
            'source' => $this->source_name($order),
            'customer_name' => (string) ($buyer['full_name'] ?? 'Не вказано'),
            'customer_phone' => (string) ($buyer['phone'] ?? ''),
            'customer_email' => (string) ($buyer['email'] ?? ''),
            'items' => $items,
            'subtotal' => $this->money((float) ($order['products_total'] ?? $order['total_price'] ?? 0), $currency),
            'shipping_total' => $this->money((float) ($order['shipping_price'] ?? 0), $currency),
            'total' => $this->money((float) ($order['grand_total'] ?? $order['total_price'] ?? 0), $currency),
            'delivery' => (string) ($delivery['shipping_service'] ?? $delivery['shipping_service_name'] ?? 'Не вказано'),
            'payment' => (string) ($payment['payment_method'] ?? $payment['payment_method_name'] ?? 'Не вказано'),
            'order_url' => 'https://hzlglobal2026.keycrm.app/orders/' . absint($order['id'] ?? 0),
        ];
    }

    private function source_name(array $order): string
    {
        return sanitize_text_field((string) ($order['source']['name'] ?? $order['source_name'] ?? 'KeyCRM'));
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2, ',', ' ') . ' ' . ($currency === 'UAH' ? '₴' : $currency);
    }

    private function recipient(): string
    {
        $email = defined('MARUDERM_ORDER_NOTIFICATION_EMAIL')
            ? (string) constant('MARUDERM_ORDER_NOTIFICATION_EMAIL')
            : 'zakaz@maruderm.com.ua';

        return sanitize_email($email);
    }

    private function settings(): array
    {
        $settings = get_option(self::SETTINGS_OPTION, []);
        return is_array($settings) ? $settings : [];
    }

    private function log(string $level, string $message, int $remote_id): void
    {
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->log($level, $message, ['source' => self::LOG_SOURCE, 'keycrm_order_id' => $remote_id]);
        }
    }
}
