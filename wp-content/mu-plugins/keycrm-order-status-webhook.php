<?php
/**
 * HPOS-safe KeyCRM to WooCommerce order-status webhook.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_KeyCRM_Order_Status_Webhook
{
    private const API_BASE_URL = 'https://openapi.keycrm.app/v1';
    private const DEDICATED_NAMESPACE = 'maruderm-keycrm/v1';
    private const DEDICATED_ROUTE = '/order-status';
    private const LEGACY_NAMESPACE = 'keycrm/v1';
    private const LEGACY_ROUTE = '/webhook';
    private const SETTINGS_OPTION = 'woocommerce_integration-keycrm_settings';
    private const EVENT_NAME = 'order.change_order_status';
    private const MAX_PAYLOAD_BYTES = 65536;
    private const EVENT_HASH_META = '_maruderm_keycrm_status_event_hash';
    private const STATUS_ID_META = '_maruderm_keycrm_status_id';
    private const STATUS_GROUP_META = '_maruderm_keycrm_status_group_id';
    private const SYNC_ORIGIN_META = '_maruderm_keycrm_status_sync_origin';
    private const LOG_SOURCE = 'maruderm-keycrm-status-webhook';

    private const STATUS_GROUP_MAP = [
        1 => 'pending',
        2 => 'processing',
        3 => 'processing',
        4 => 'processing',
        5 => 'completed',
        6 => 'cancelled',
    ];

    private const STATUS_ID_MAP = [
        1 => 'keycrm-new',
        2 => 'confirmed',
        4 => 'wait-prepayment',
        8 => 'ttn-created',
        9 => 'ready-to-send',
        10 => 'departing',
        12 => 'completed',
        19 => 'cancelled',
    ];

    private const CUSTOM_STATUSES = [
        'keycrm-new' => 'New',
        'confirmed' => 'Confirmed',
        'wait-prepayment' => 'Waiting for Prepayment',
        'ttn-created' => 'TTN Created',
        'ready-to-send' => 'Ready to Send',
        'departing' => 'Departing',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'register_order_statuses']);
        add_filter('wc_order_statuses', [$this, 'add_order_statuses']);
        add_filter('woocommerce_order_is_paid', [$this, 'preserve_paid_state'], 10, 2);
        add_action('rest_api_init', [$this, 'register_routes'], 100);
    }

    public function register_order_statuses(): void
    {
        foreach (self::CUSTOM_STATUSES as $status => $label) {
            register_post_status(
                'wc-' . $status,
                [
                    'label' => $label,
                    'public' => false,
                    'exclude_from_search' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop(
                        $label . ' <span class="count">(%s)</span>',
                        $label . ' <span class="count">(%s)</span>'
                    ),
                ]
            );
        }
    }

    public function add_order_statuses(array $statuses): array
    {
        $ordered_statuses = [];

        foreach (self::CUSTOM_STATUSES as $status => $label) {
            $ordered_statuses['wc-' . $status] = $label;
        }

        foreach ($statuses as $status => $label) {
            $ordered_statuses[$status] = $label;
        }

        return $ordered_statuses;
    }

    public function preserve_paid_state(bool $is_paid, $order): bool
    {
        if ($is_paid || ! $order instanceof WC_Order) {
            return $is_paid;
        }

        return isset(self::CUSTOM_STATUSES[$order->get_status()])
            && $order->get_date_paid() !== null;
    }

    public function register_routes(): void
    {
        $route = [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handle_status_request'],
            'permission_callback' => [$this, 'authorize'],
        ];

        register_rest_route(
            self::DEDICATED_NAMESPACE,
            self::DEDICATED_ROUTE,
            $route
        );

        register_rest_route(
            self::LEGACY_NAMESPACE,
            self::LEGACY_ROUTE,
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'handle_legacy_request'],
                'permission_callback' => [$this, 'authorize'],
            ],
            true
        );
    }

    public function authorize(WP_REST_Request $request)
    {
        $settings = get_option(self::SETTINGS_OPTION, []);
        $expected = is_array($settings)
            ? trim((string) ($settings['webhook_secret_key'] ?? ''))
            : '';
        $provided = trim((string) $request->get_header('X-KeyCRM-Webhook-Secret'));

        if ($provided === '') {
            $provided = trim((string) $request->get_param('secret'));
        }

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return new WP_Error(
                'maruderm_keycrm_unauthorized',
                'Invalid webhook secret.',
                ['status' => 401]
            );
        }

        return true;
    }

    public function handle_legacy_request(WP_REST_Request $request)
    {
        $payload = $this->payload($request);

        if (is_wp_error($payload)) {
            return $payload;
        }

        if (($payload['event'] ?? '') === self::EVENT_NAME) {
            return $this->synchronize($payload);
        }

        if (class_exists('WC_Keycrm_Webhook_Handler')) {
            return WC_Keycrm_Webhook_Handler::handle_rest_webhook($request);
        }

        return new WP_Error(
            'maruderm_keycrm_vendor_handler_missing',
            'The vendor webhook handler is unavailable.',
            ['status' => 503]
        );
    }

    public function handle_status_request(WP_REST_Request $request)
    {
        $payload = $this->payload($request);

        if (is_wp_error($payload)) {
            return $payload;
        }

        if (($payload['event'] ?? '') !== self::EVENT_NAME) {
            return new WP_Error(
                'maruderm_keycrm_unsupported_event',
                'Unsupported webhook event.',
                ['status' => 422]
            );
        }

        return $this->synchronize($payload);
    }

    private function synchronize(array $payload)
    {
        $context = isset($payload['context']) && is_array($payload['context'])
            ? $payload['context']
            : [];
        $order_id = absint($context['source_uuid'] ?? 0);
        $status_group_id = absint($context['status_group_id'] ?? 0);

        if ($order_id <= 0 || $status_group_id <= 0) {
            return new WP_Error(
                'maruderm_keycrm_invalid_context',
                'The webhook context is missing source_uuid or status_group_id.',
                ['status' => 422]
            );
        }

        $order = wc_get_order($order_id);

        if (! $order instanceof WC_Order) {
            return new WP_Error(
                'maruderm_keycrm_order_not_found',
                'WooCommerce order not found.',
                ['status' => 404]
            );
        }

        $remote_order_id = absint($context['id'] ?? ($context['order_id'] ?? 0));
        $stored_remote_order_id = absint($order->get_meta('_keycrm_order_id'));

        if ($remote_order_id > 0 && $stored_remote_order_id > 0 && $remote_order_id !== $stored_remote_order_id) {
            return new WP_Error(
                'maruderm_keycrm_order_identity_mismatch',
                'The KeyCRM order identity does not match WooCommerce metadata.',
                ['status' => 409]
            );
        }

        $remote_order_id = $remote_order_id > 0 ? $remote_order_id : $stored_remote_order_id;
        $status_id = $this->remote_status_id($remote_order_id, $order_id, $status_group_id);
        $target_status = $this->target_status($status_id, $status_group_id);

        if ($target_status === '' || ! array_key_exists('wc-' . $target_status, wc_get_order_statuses())) {
            $this->log('warning', 'Rejected an unmapped KeyCRM status.', $order_id, $status_group_id);

            return new WP_Error(
                'maruderm_keycrm_unmapped_status',
                'The KeyCRM status is not mapped.',
                ['status' => 422]
            );
        }

        $event_hash = hash(
            'sha256',
            wp_json_encode([
                'event' => self::EVENT_NAME,
                'order_id' => $order_id,
                'remote_order_id' => $remote_order_id,
                'status_id' => $status_id,
                'status_group_id' => $status_group_id,
                'target_status' => $target_status,
            ])
        );
        $last_event_hash = (string) $order->get_meta(self::EVENT_HASH_META);

        if ($last_event_hash !== '' && hash_equals($last_event_hash, $event_hash)) {
            return new WP_REST_Response([
                'ok' => true,
                'result' => 'duplicate',
                'order_id' => $order_id,
                'status' => $order->get_status(),
            ], 200);
        }

        $previous_status = $order->get_status();

        try {
            if ($previous_status !== $target_status) {
                $order->update_status(
                    $target_status,
                    $status_id > 0
                        ? sprintf('KeyCRM status %d synchronized.', $status_id)
                        : sprintf('KeyCRM status group %d synchronized.', $status_group_id),
                    true
                );
                $order = wc_get_order($order_id);
            }

            if (! $order instanceof WC_Order) {
                throw new RuntimeException('WooCommerce order could not be reloaded after status synchronization.');
            }

            $order->update_meta_data(self::EVENT_HASH_META, $event_hash);
            $order->update_meta_data(self::STATUS_ID_META, $status_id);
            $order->update_meta_data(self::STATUS_GROUP_META, $status_group_id);
            $order->update_meta_data(self::SYNC_ORIGIN_META, 'keycrm');
            $order->save_meta_data();
        } catch (Throwable $exception) {
            $this->log('error', 'WooCommerce status synchronization failed.', $order_id, $status_group_id);

            return new WP_Error(
                'maruderm_keycrm_status_update_failed',
                'WooCommerce status synchronization failed.',
                ['status' => 500]
            );
        }

        $result = $previous_status === $target_status ? 'unchanged' : 'updated';
        $this->log('notice', 'KeyCRM status synchronized to WooCommerce.', $order_id, $status_group_id);

        return new WP_REST_Response([
            'ok' => true,
            'result' => $result,
            'order_id' => $order_id,
            'previous_status' => $previous_status,
            'status' => $target_status,
            'keycrm_status_id' => $status_id,
        ], 200);
    }

    private function target_status(int $status_id, int $status_group_id): string
    {
        $status_id_map = apply_filters(
            'maruderm_keycrm_status_id_map',
            self::STATUS_ID_MAP
        );

        if (is_array($status_id_map) && isset($status_id_map[$status_id])) {
            return sanitize_key((string) $status_id_map[$status_id]);
        }

        $status_group_map = apply_filters(
            'maruderm_keycrm_status_group_map',
            self::STATUS_GROUP_MAP
        );

        return is_array($status_group_map)
            ? sanitize_key((string) ($status_group_map[$status_group_id] ?? ''))
            : '';
    }

    private function remote_status_id(int $remote_order_id, int $order_id, int $status_group_id): int
    {
        $api_token = $this->api_token();

        if ($remote_order_id <= 0 || $api_token === '') {
            $this->log('warning', 'Exact KeyCRM status lookup is unavailable; using the status-group fallback.', $order_id, $status_group_id);
            return 0;
        }

        $response = wp_remote_get(
            self::API_BASE_URL . '/order/' . $remote_order_id . '?include=status',
            [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_token,
                    'Accept' => 'application/json',
                ],
            ]
        );

        if (is_wp_error($response)) {
            $this->log('warning', 'Exact KeyCRM status lookup failed; using the status-group fallback.', $order_id, $status_group_id);
            return 0;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($status_code < 200 || $status_code >= 300 || ! is_array($body)) {
            $this->log('warning', 'Exact KeyCRM status lookup returned an invalid response; using the status-group fallback.', $order_id, $status_group_id);
            return 0;
        }

        return isset($body['status']) && is_array($body['status'])
            ? absint($body['status']['id'] ?? 0)
            : 0;
    }

    private function api_token(): string
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        return is_array($settings) ? trim((string) ($settings['api_key'] ?? '')) : '';
    }

    private function payload(WP_REST_Request $request)
    {
        if (strlen($request->get_body()) > self::MAX_PAYLOAD_BYTES) {
            return new WP_Error(
                'maruderm_keycrm_payload_too_large',
                'Webhook payload is too large.',
                ['status' => 413]
            );
        }

        $payload = $request->get_json_params();

        if (! is_array($payload)) {
            return new WP_Error(
                'maruderm_keycrm_invalid_json',
                'Webhook payload must be a JSON object.',
                ['status' => 400]
            );
        }

        return $payload;
    }

    private function log(string $level, string $message, int $order_id, int $status_group_id): void
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
                'status_group_id' => $status_group_id,
            ]
        );
    }
}

(new Maruderm_KeyCRM_Order_Status_Webhook())->register();
