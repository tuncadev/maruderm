<?php
/**
 * Repairs missing KeyCRM payment synchronization for paid WooCommerce orders.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_KeyCRM_Order_Payment_Sync
{
    private const API_BASE_URL = 'https://openapi.keycrm.app/v1';
    private const SETTINGS_OPTION = 'woocommerce_integration-keycrm_settings';
    private const ORDER_ID_META = '_keycrm_order_id';
    private const PAYMENT_ID_META = '_keycrm_payment_id';
    private const PAYMENT_STATUS_META = '_keycrm_last_payment_status';
    private const LOG_SOURCE = 'maruderm-keycrm-payment-sync';

    /** @var array<int, bool> */
    private array $processed_orders = [];

    public function register(): void
    {
        add_action('woocommerce_order_status_changed', [$this, 'handle_status_change'], 20, 4);
        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete'], 20);
    }

    public function handle_status_change(int $order_id, string $from, string $to, $order): void
    {
        $this->synchronize($order_id, $order instanceof WC_Order ? $order : null);
    }

    public function handle_payment_complete(int $order_id): void
    {
        $this->synchronize($order_id);
    }

    private function synchronize(int $order_id, ?WC_Order $order = null): void
    {
        if (isset($this->processed_orders[$order_id]) || ! function_exists('wc_get_order')) {
            return;
        }

        $this->processed_orders[$order_id] = true;
        $order = $order ?: wc_get_order($order_id);

        if (! $order instanceof WC_Order || ! $order->is_paid()) {
            return;
        }

        $keycrm_order_id = absint($order->get_meta(self::ORDER_ID_META));
        $payment_method_id = $this->payment_method_id($order);
        $api_token = $this->api_token();

        if ($keycrm_order_id <= 0 || $payment_method_id <= 0 || $api_token === '') {
            $this->log('warning', 'Skipped paid payment synchronization because the order mapping, payment mapping, or API token is unavailable.', $order_id);
            return;
        }

        $remote_order = $this->request('GET', '/order/' . $keycrm_order_id . '?include=payments', $api_token);

        if (is_wp_error($remote_order)) {
            $this->log('error', $remote_order->get_error_message(), $order_id);
            return;
        }

        $payments = isset($remote_order['payments']) && is_array($remote_order['payments'])
            ? $remote_order['payments']
            : [];

        if ($payments === []) {
            $this->create_payment($order, $keycrm_order_id, $payment_method_id, $api_token);
            return;
        }

        $payment = reset($payments);
        $payment_id = is_array($payment) ? absint($payment['id'] ?? 0) : 0;

        if ($payment_id <= 0) {
            $this->log('error', 'KeyCRM returned a payment without an ID.', $order_id);
            return;
        }

        $order->update_meta_data(self::PAYMENT_ID_META, $payment_id);

        if ((string) ($payment['status'] ?? '') !== 'paid') {
            $result = $this->request(
                'PUT',
                '/order/' . $keycrm_order_id . '/payment/' . $payment_id,
                $api_token,
                ['status' => 'paid']
            );

            if (is_wp_error($result)) {
                $order->save_meta_data();
                $this->log('error', $result->get_error_message(), $order_id);
                return;
            }
        }

        $order->update_meta_data(self::PAYMENT_STATUS_META, 'paid');
        $order->save_meta_data();
    }

    private function create_payment(WC_Order $order, int $keycrm_order_id, int $payment_method_id, string $api_token): void
    {
        $paid_at = $order->get_date_paid();
        $payment_date = $paid_at
            ? gmdate('Y-m-d H:i:s', $paid_at->getTimestamp())
            : gmdate('Y-m-d H:i:s');

        $result = $this->request(
            'POST',
            '/order/' . $keycrm_order_id . '/payment',
            $api_token,
            [
                'payment_method_id' => $payment_method_id,
                'amount' => (float) $order->get_total(),
                'status' => 'paid',
                'payment_date' => $payment_date,
            ]
        );

        if (is_wp_error($result)) {
            $this->log('error', $result->get_error_message(), $order->get_id());
            return;
        }

        $payment_id = absint($result['id'] ?? ($result['data']['id'] ?? 0));

        if ($payment_id <= 0) {
            $remote_order = $this->request('GET', '/order/' . $keycrm_order_id . '?include=payments', $api_token);
            $payments = ! is_wp_error($remote_order) && isset($remote_order['payments']) && is_array($remote_order['payments'])
                ? $remote_order['payments']
                : [];
            $payment = reset($payments);
            $payment_id = is_array($payment) ? absint($payment['id'] ?? 0) : 0;
        }

        if ($payment_id > 0) {
            $order->update_meta_data(self::PAYMENT_ID_META, $payment_id);
        }

        $order->update_meta_data(self::PAYMENT_STATUS_META, 'paid');
        $order->save_meta_data();
    }

    private function request(string $method, string $path, string $api_token, array $body = [])
    {
        $args = [
            'method' => $method,
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
                'Accept' => 'application/json',
            ],
        ];

        if ($body !== []) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request(self::API_BASE_URL . $path, $args);

        if (is_wp_error($response)) {
            return new WP_Error('keycrm_transport_error', 'KeyCRM payment request failed: ' . $response->get_error_message());
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error('keycrm_http_error', 'KeyCRM payment request returned HTTP ' . $status_code . '.');
        }

        return is_array($response_body) ? $response_body : [];
    }

    private function payment_method_id(WC_Order $order): int
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        if (! is_array($settings)) {
            return 0;
        }

        return absint($settings[$order->get_payment_method()] ?? 0);
    }

    private function api_token(): string
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        return is_array($settings) ? trim((string) ($settings['api_key'] ?? '')) : '';
    }

    private function log(string $level, string $message, int $order_id): void
    {
        if (! function_exists('wc_get_logger')) {
            return;
        }

        wc_get_logger()->log(
            $level,
            $message,
            [
                'source' => self::LOG_SOURCE,
                'order_id' => $order_id,
            ]
        );
    }
}

(new Maruderm_KeyCRM_Order_Payment_Sync())->register();
