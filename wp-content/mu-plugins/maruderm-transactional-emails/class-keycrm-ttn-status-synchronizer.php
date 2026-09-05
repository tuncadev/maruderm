<?php
/**
 * Promotes newly tracked KeyCRM orders to the TTN-created status.
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
    private const MAX_STATE_ENTRIES = 500;
    private const LOG_SOURCE = 'maruderm-keycrm-ttn-status-sync';

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
        if (get_transient(self::LOCK_KEY) !== false) {
            return;
        }

        set_transient(self::LOCK_KEY, '1', 110);

        try {
            $orders = $this->tracked_orders();

            if (is_wp_error($orders)) {
                $this->log('error', 'KeyCRM tracked-order lookup failed.');
                return;
            }

            $state = $this->state();

            if (empty($state['initialized'])) {
                $this->save_state($this->baseline_state($orders));
                $this->log('notice', 'Existing KeyCRM TTNs were recorded as the initial baseline.');
                return;
            }

            $seen = is_array($state['seen'] ?? null) ? $state['seen'] : [];

            foreach ($orders as $order) {
                if (! is_array($order)) {
                    continue;
                }

                $remote_id = absint($order['id'] ?? 0);
                $tracking_code = $this->tracking_code($order);

                if ($remote_id <= 0 || $tracking_code === '') {
                    continue;
                }

                $fingerprint = $this->fingerprint($remote_id, $tracking_code);

                if (isset($seen[(string) $remote_id]) && hash_equals((string) $seen[(string) $remote_id], $fingerprint)) {
                    continue;
                }

                $status_id = absint($order['status_id'] ?? ($order['status']['id'] ?? 0));

                if ($status_id === self::TARGET_STATUS_ID || ! in_array($status_id, self::ELIGIBLE_STATUS_IDS, true)) {
                    $seen[(string) $remote_id] = $fingerprint;
                    continue;
                }

                if (! $this->promote($remote_id, $tracking_code)) {
                    continue;
                }

                $seen[(string) $remote_id] = $fingerprint;
                $this->log('notice', 'KeyCRM order promoted after a new TTN was detected.', $remote_id);
            }

            $state['seen'] = array_slice($seen, -self::MAX_STATE_ENTRIES, null, true);
            $state['checked_at'] = gmdate('c');
            $this->save_state($state);
        } finally {
            delete_transient(self::LOCK_KEY);
        }
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

    private function promote(int $remote_id, string $tracking_code): bool
    {
        $token = $this->api_token();
        $updated = $this->request(
            'PUT',
            self::API_BASE_URL . '/order/' . $remote_id,
            ['status_id' => self::TARGET_STATUS_ID],
            $token
        );

        if (is_wp_error($updated)) {
            $this->log('error', 'KeyCRM rejected the automatic TTN-created status update.', $remote_id);
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

        return $verified_status === self::TARGET_STATUS_ID
            && $verified_tracking !== ''
            && hash_equals($tracking_code, $verified_tracking);
    }

    private function request(string $method, string $url, ?array $body, string $token)
    {
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
