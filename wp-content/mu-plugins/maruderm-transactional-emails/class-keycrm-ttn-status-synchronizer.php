<?php
/**
 * Reconciles KeyCRM order statuses with the carrier shipment lifecycle.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_KeyCRM_TTN_Status_Synchronizer
{
    public const CRON_HOOK = 'maruderm_keycrm_sync_new_ttn_statuses';

    private const API_BASE_URL = 'https://openapi.keycrm.app/v1';
    private const SETTINGS_OPTION = 'woocommerce_integration-keycrm_settings';
    private const STATE_OPTION = 'maruderm_keycrm_ttn_status_sync_state';
    private const LOCK_KEY = 'maruderm_keycrm_ttn_status_sync_lock';
    private const TARGET_STATUS_ID = 8;
    private const ELIGIBLE_STATUS_IDS = [1, 2, 4, 20];
    private const ACTIVE_STATUS_IDS = [1, 2, 4, 20, 8, 9, 10];
    private const MAX_STATE_ENTRIES = 500;
    private const LOG_SOURCE = 'maruderm-keycrm-ttn-status-sync';

    private float $last_request_at = 0.0;
    private float $deadline = 0.0;

    public function register(): void
    {
        add_filter('cron_schedules', [$this, 'add_schedule']);
        add_action('init', [$this, 'ensure_scheduled']);
        add_action(self::CRON_HOOK, [$this, 'run']);
    }

    public function add_schedule(array $schedules): array
    {
        $schedules['maruderm_every_two_minutes'] = [
            'interval' => 2 * MINUTE_IN_SECONDS,
            'display' => 'Every two minutes',
        ];

        return $schedules;
    }

    public function ensure_scheduled(): void
    {
        if (wp_next_scheduled(self::CRON_HOOK) === false) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'maruderm_every_two_minutes', self::CRON_HOOK);
        }
    }

    public function run(): void
    {
        $lock = ['owner' => wp_generate_uuid4(), 'expires' => time() + 600];
        $previous_lock = get_option(self::LOCK_KEY);

        if (is_array($previous_lock) && (int) ($previous_lock['expires'] ?? 0) < time()) {
            $this->release_lock($previous_lock);
        }

        if (! add_option(self::LOCK_KEY, $lock, '', false)) {
            return;
        }

        $this->deadline = microtime(true) + 240;

        try {
            $orders = $this->tracked_orders();

            if (is_wp_error($orders)) {
                $this->log('error', 'KeyCRM tracked-order lookup failed.');
                return;
            }

            $state = $this->state();

            $state['initialized'] = true;
            $state['initialized_at'] = $state['initialized_at'] ?? gmdate('c');

            $seen = is_array($state['seen'] ?? null) ? $state['seen'] : [];
            $updated = 0;

            foreach ($orders as $order) {
                if (! is_array($order)) {
                    continue;
                }

                $remote_id = absint($order['id'] ?? 0);
                $tracking_code = $this->tracking_code($order);

                if ($remote_id <= 0 || $tracking_code === '') {
                    continue;
                }

                $shipment = $this->shipment_status($order);
                $fingerprint = hash('sha256', $remote_id . ':' . $tracking_code . ':' . $shipment . ':' . $this->status_id($order));
                $target = $this->target_status($order);

                if ($target === null) {
                    $this->synchronize_site_order($order);
                    if (($seen[(string) $remote_id] ?? '') !== $fingerprint
                        && in_array($this->status_id($order), self::ACTIVE_STATUS_IDS, true)
                        && ! in_array($shipment, ['', 'invoice', 'transit', 'pickup', 'delivered', 'cash_on_delivery', 'cash_recived', 'returned'], true)) {
                        $this->log('warning', 'Shipment requires manual review; order and payment statuses preserved.', $remote_id);
                    }
                    $seen[(string) $remote_id] = $fingerprint;
                    continue;
                }

                if (microtime(true) >= $this->deadline - 45) {
                    $this->log('warning', 'Shipment synchronization time budget reached; remaining orders will retry.');
                    break;
                }

                if (! $this->promote($remote_id, $tracking_code, $target)) {
                    continue;
                }

                $seen[(string) $remote_id] = $fingerprint;
                ++$updated;
                $this->log('notice', sprintf('Shipment status %s synchronized to KeyCRM status %d.', $shipment ?: 'invoice', $target), $remote_id);
            }

            $state['seen'] = array_slice($seen, -self::MAX_STATE_ENTRIES, null, true);
            $state['checked_at'] = gmdate('c');
            $this->save_state($state);
            $this->log('info', sprintf('Shipment synchronization checked %d orders and updated %d.', count($orders), $updated));
        } finally {
            $this->release_lock($lock);
            $this->deadline = 0.0;
        }
    }

    /** Read-only reconciliation plan, suitable for WP-CLI diagnostics. */
    public function preview()
    {
        $orders = $this->tracked_orders();

        if (is_wp_error($orders)) {
            return $orders;
        }

        return array_map(fn (array $order): array => [
            'order_id' => absint($order['id'] ?? 0),
            'current_status_id' => $this->status_id($order),
            'shipment_status' => $this->shipment_status($order),
            'target_status_id' => $this->target_status($order),
        ], $orders);
    }

    private function release_lock(array $lock): void
    {
        global $wpdb;

        $deleted = $wpdb->delete($wpdb->options, [
            'option_name' => self::LOCK_KEY,
            'option_value' => maybe_serialize($lock),
        ], ['%s', '%s']);

        if ($deleted) {
            wp_cache_delete(self::LOCK_KEY, 'options');
        }
    }

    private function status_id(array $order): int
    {
        return absint($order['status_id'] ?? ($order['status']['id'] ?? 0));
    }

    private function shipment_status(array $order): string
    {
        $shipping = is_array($order['shipping'] ?? null) ? $order['shipping'] : [];
        $history = $shipping['last_history'] ?? $shipping['lastHistory'] ?? [];

        if (is_array($history) && ! empty($history['tracking_code'])
            && ! hash_equals($this->tracking_code($order), trim((string) $history['tracking_code']))) {
            return 'tracking_mismatch';
        }

        return strtolower(trim((string) ($history['shipping_status'] ?? $shipping['shipping_status'] ?? '')));
    }

    private function target_status(array $order): ?int
    {
        $current = $this->status_id($order);

        if ($this->tracking_code($order) === '' || ! in_array($current, self::ACTIVE_STATUS_IDS, true)) {
            return null;
        }

        $target = match ($this->shipment_status($order)) {
            '', 'invoice' => in_array($current, self::ELIGIBLE_STATUS_IDS, true) ? self::TARGET_STATUS_ID : null,
            'transit', 'pickup' => 10,
            'delivered', 'cash_on_delivery', 'cash_recived' => 12,
            'returned' => 19,
            default => null,
        };

        return $target === $current ? null : $target;
    }

    private function tracked_orders()
    {
        $token = $this->api_token();

        if ($token === '') {
            return new WP_Error('maruderm_keycrm_token_missing', 'KeyCRM API token is unavailable.');
        }

        $orders = [];
        $page = 1;

        do {
            $url = add_query_arg(
                [
                    'filter[has_tracking_code]' => 1,
                    'include' => 'status,shipping.lastHistory',
                    'limit' => 50,
                    'page' => $page,
                ],
                self::API_BASE_URL . '/order'
            );
            $response = $this->request('GET', $url, null, $token);

            if (is_wp_error($response)) {
                return $response;
            }

            $orders = array_merge($orders, (array) ($response['data'] ?? []));
            $last_page = max(1, absint($response['last_page'] ?? 1));
            ++$page;
        } while ($page <= $last_page);

        return $orders;
    }

    private function promote(int $remote_id, string $tracking_code, int $target_status): bool
    {
        $token = $this->api_token();
        $fresh = $this->request('GET', self::API_BASE_URL . '/order/' . $remote_id . '?include=status,shipping.lastHistory', null, $token);

        if (is_wp_error($fresh) || ! hash_equals($tracking_code, $this->tracking_code($fresh))) {
            return false;
        }

        if ($this->status_id($fresh) === $target_status) {
            $this->synchronize_site_order($fresh);
            return true;
        }

        if ($this->target_status($fresh) !== $target_status) {
            return false;
        }

        $updated = $this->request(
            'PUT',
            self::API_BASE_URL . '/order/' . $remote_id,
            ['status_id' => $target_status],
            $token
        );

        if (is_wp_error($updated)) {
            $this->log('error', 'KeyCRM rejected the shipment status update.', $remote_id);
            return false;
        }

        $verified = $this->request(
            'GET',
            self::API_BASE_URL . '/order/' . $remote_id . '?include=status,shipping.lastHistory',
            null,
            $token
        );

        if (is_wp_error($verified)) {
            $this->log('error', 'KeyCRM status update could not be verified.', $remote_id);
            return false;
        }

        $verified_status = absint($verified['status_id'] ?? ($verified['status']['id'] ?? 0));
        $verified_tracking = $this->tracking_code($verified);

        $matches = $verified_status === $target_status
            && $verified_tracking !== ''
            && hash_equals($tracking_code, $verified_tracking);

        if ($matches) {
            $this->synchronize_site_order($verified);
        }

        return $matches;
    }

    private function synchronize_site_order(array $remote): void
    {
        if (absint($remote['source_id'] ?? 0) !== 2
            || ! class_exists('Maruderm_KeyCRM_Order_Status_Webhook')
            || ! class_exists('Maruderm_KeyCRM_Status_Config')) {
            return;
        }

        $local_id = (string) ($remote['source_uuid'] ?? '');
        $remote_id = absint($remote['id'] ?? 0);

        if (! ctype_digit($local_id) || $remote_id <= 0) {
            return;
        }

        $order = wc_get_order((int) $local_id);

        if (! $order instanceof WC_Order || absint($order->get_meta('_keycrm_order_id')) !== $remote_id) {
            $this->log('warning', 'Website reconciliation skipped an unlinked order.', $remote_id);
            return;
        }

        $status_id = $this->status_id($remote);
        $group_id = absint($remote['status']['group_id'] ?? 0);
        $config = Maruderm_KeyCRM_Status_Config::instance();
        $target = $config->target_status($status_id, $group_id);

        if ($target === '' || $order->get_status() === $target) {
            return;
        }

        if (in_array($order->get_status(), ['completed', 'cancelled', 'refunded'], true)) {
            $this->log('warning', 'Website terminal status differs from KeyCRM; manual review required.', $remote_id);
            return;
        }

        $settings = get_option(self::SETTINGS_OPTION, []);
        $request = new WP_REST_Request('POST');
        $request->set_header('Content-Type', 'application/json');
        $request->set_header('X-KeyCRM-Webhook-Secret', (string) ($settings['webhook_secret_key'] ?? ''));
        $request->set_body(wp_json_encode([
            'event' => 'order.change_order_status',
            'context' => [
                'id' => $remote_id,
                'source_uuid' => $local_id,
                'status_group_id' => $group_id,
            ],
        ]));
        $handler = new Maruderm_KeyCRM_Order_Status_Webhook($config);

        if (is_wp_error($handler->authorize($request))) {
            $this->log('error', 'Website reconciliation requires a valid configured webhook secret.', $remote_id);
            return;
        }

        if ($this->deadline > 0 && microtime(true) >= $this->deadline - 20) {
            return;
        }

        $wait = 1.1 - (microtime(true) - $this->last_request_at);
        if ($wait > 0) {
            usleep((int) ceil($wait * 1000000));
        }
        $this->last_request_at = microtime(true);
        $result = $handler->handle_status_request($request);

        if (is_wp_error($result)) {
            $this->log('error', 'Website reconciliation failed; it will be retried on the next poll.', $remote_id);
        } else {
            $this->log('notice', 'Website order reconciled through the existing status handler.', $remote_id);
        }
    }

    private function request(string $method, string $url, ?array $body, string $token)
    {
        if ($this->deadline > 0 && microtime(true) >= $this->deadline - 20) {
            return new WP_Error('maruderm_keycrm_sync_deadline', 'Shipment synchronization deadline reached.');
        }

        $wait = 1.1 - (microtime(true) - $this->last_request_at);
        if ($wait > 0) {
            usleep((int) ceil($wait * 1000000));
        }
        $this->last_request_at = microtime(true);

        $args = [
            'method' => $method,
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ];

        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($status < 200 || $status >= 300 || ! is_array($decoded)) {
            $this->log('error', sprintf('KeyCRM shipment request failed with HTTP %d.', $status));
            if (in_array($status, [401, 403, 429], true)) {
                $this->deadline = microtime(true);
            }
            return new WP_Error('maruderm_keycrm_invalid_response', 'KeyCRM returned an invalid response.');
        }

        return $decoded;
    }

    private function baseline_state(array $orders): array
    {
        $seen = [];

        foreach ($orders as $order) {
            if (! is_array($order)) {
                continue;
            }

            $remote_id = absint($order['id'] ?? 0);
            $tracking_code = $this->tracking_code($order);

            if ($remote_id > 0 && $tracking_code !== '') {
                $seen[(string) $remote_id] = $this->fingerprint($remote_id, $tracking_code);
            }
        }

        return [
            'initialized' => true,
            'initialized_at' => gmdate('c'),
            'checked_at' => gmdate('c'),
            'seen' => array_slice($seen, -self::MAX_STATE_ENTRIES, null, true),
        ];
    }

    private function tracking_code(array $order): string
    {
        $shipping = is_array($order['shipping'] ?? null) ? $order['shipping'] : [];
        return trim((string) ($shipping['tracking_code'] ?? ''));
    }

    private function fingerprint(int $remote_id, string $tracking_code): string
    {
        return hash('sha256', $remote_id . ':' . $tracking_code);
    }

    private function state(): array
    {
        $state = get_option(self::STATE_OPTION, []);
        return is_array($state) ? $state : [];
    }

    private function save_state(array $state): void
    {
        update_option(self::STATE_OPTION, $state, false);
    }

    private function api_token(): string
    {
        $settings = get_option(self::SETTINGS_OPTION, []);
        return is_array($settings) ? trim((string) ($settings['api_key'] ?? '')) : '';
    }

    private function log(string $level, string $message, int $remote_id = 0): void
    {
        if (! function_exists('wc_get_logger')) {
            return;
        }

        $context = ['source' => self::LOG_SOURCE];

        if ($remote_id > 0) {
            $context['keycrm_order_id'] = $remote_id;
        }

        wc_get_logger()->log($level, $message, $context);
    }
}
