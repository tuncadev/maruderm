<?php
/** Bidirectional order-stage coordination; payment completion stays separate. */
if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_KeyCRM_Order_Status_Sync
{
    public const PENDING = '_maruderm_keycrm_pending_status';
    private const ERROR = '_maruderm_keycrm_status_sync_error';
    private static array $incoming = [];
    private static float $lastRequest = 0;

    public function register(): void
    {
        add_action('wp_loaded', [$this, 'separatePayments'], 100);
        add_action('woocommerce_order_status_changed', [$this, 'changed'], 1, 4);
        add_action('woocommerce_payment_complete', [$this, 'paymentCompleted'], 30);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'created'], 50);
        add_action('woocommerce_checkout_order_processed', [$this, 'created'], 50);
        add_action('maruderm_keycrm_sync_new_ttn_statuses', [$this, 'retryPending'], 5);
    }

    public static function incoming(int $id, bool $active): void
    {
        if ($active) self::$incoming[$id] = true;
        else unset(self::$incoming[$id]);
    }

    public static function target(string $status): int
    {
        $config = Maruderm_KeyCRM_Status_Config::instance();
        $matches = [];
        foreach ($config->mappings() as $id => $mapping) {
            if (! empty($mapping['include']) && ($mapping['slug'] ?? '') === $status) $matches[] = (int) $id;
        }
        if (count($matches) === 1) return $matches[0];
        if (count($matches) > 1) return 0;
        $aliases = apply_filters('maruderm_keycrm_native_status_map', [
            'pending' => 1, 'processing' => 2, 'on-hold' => 4,
            'completed' => 12, 'cancelled' => 19,
        ]);
        $id = (int) ($aliases[$status] ?? 0);
        return isset($config->mappings()[$id]) ? $id : 0;
    }

    public static function terminal(string $status): bool
    {
        if (in_array($status, ['completed', 'cancelled', 'refunded', 'failed'], true)) return true;
        foreach (Maruderm_KeyCRM_Status_Config::instance()->mappings() as $mapping) {
            if (($mapping['slug'] ?? '') === $status && in_array($mapping['fallback'] ?? '', ['completed', 'cancelled', 'refunded', 'failed'], true)) return true;
        }
        return false;
    }

    public function separatePayments(): void
    {
        foreach (($GLOBALS['wp_filter']['woocommerce_order_status_changed']->callbacks ?? []) as $priority => $hooks) {
            foreach ($hooks as $hook) {
                $callback = $hook['function'];
                if (! is_array($callback) || ! is_object($callback[0])) continue;
                if ((is_a($callback[0], 'WC_Keycrm_Base') && $callback[1] === 'update_order_status')
                    || (is_a($callback[0], 'Maruderm_KeyCRM_Order_Payment_Sync') && $callback[1] === 'handle_status_change')) {
                    remove_action('woocommerce_order_status_changed', $callback, $priority);
                }
            }
        }
    }

    public function created($order): void
    {
        $order = $order instanceof WC_Order ? $order : wc_get_order((int) $order);
        if ($order) $this->changed($order->get_id(), '', $order->get_status(), $order);
    }

    public function paymentCompleted(int $id): void
    {
        $order = wc_get_order($id);
        // Processing can be unpaid COD. A payment-complete event plus a recorded
        // payment is required; do not regress fulfillment or terminal stages.
        if (! $order instanceof WC_Order || ! $order->get_meta('_keycrm_order_id')
            || ! $order->is_paid() || $order->get_date_paid() === null
            || ! in_array(self::target($order->get_status()), [1, 2, 4], true)
            || self::terminal($order->get_status())) return;

        $mapping = Maruderm_KeyCRM_Status_Config::instance()->mappings()[20] ?? [];
        $paid = (string) ($mapping['slug'] ?? '');
        if (empty($mapping['include']) || $paid === '' || self::target($paid) !== 20) return;

        // Reuse the identity checks, pending retries, and loop protection in
        // changed(). This transition never creates or edits a payment entry.
        $order->update_status($paid, 'Payment confirmed; advancing to Paid.', false);
    }

    public function changed(int $id, string $from, string $to, $order): void
    {
        // Also handles integration callbacks registered after wp_loaded.
        $this->separatePayments();
        if (isset(self::$incoming[$id]) || $from === $to || ! $order instanceof WC_Order
            || ! $order->get_meta('_keycrm_order_id') || $order->get_status() !== $to) return;
        $order->update_meta_data(self::PENDING, $to);
        $order->save_meta_data();
        $this->send($id);
    }

    public function retryPending(): void
    {
        // Bounded work on the existing five-minute production scheduler.
        $mapped = [];
        foreach (array_keys(wc_get_order_statuses()) as $status) {
            $slug = substr($status, 3);
            if (self::target($slug) > 0) $mapped[] = $slug;
        }
        if ($mapped === []) return;
        $deadline = microtime(true) + 35;
        foreach (wc_get_orders(['limit' => 10, 'return' => 'ids', 'orderby' => 'modified', 'order' => 'ASC',
            'status' => array_keys(wc_get_order_statuses()),
            'meta_query' => [['key' => self::PENDING, 'value' => $mapped, 'compare' => 'IN']]]) as $id) {
            if (microtime(true) >= $deadline) break;
            $this->send((int) $id);
        }
    }

    public function send(int $id): bool
    {
        $lock = self::lock($id);
        if ($lock === '') return false;
        try {
            $order = wc_get_order($id);
            if (! $order) return false;
            $status = (string) $order->get_meta(self::PENDING);
            if ($status === '') return true;
            if ($status !== $order->get_status()) {
                $order->delete_meta_data(self::PENDING);
                $order->save_meta_data();
                return false;
            }
            $target = self::target($status);
            if (! $target) throw new RuntimeException('No unique KeyCRM mapping for WooCommerce status: ' . $status);
            $remoteId = (int) $order->get_meta('_keycrm_order_id');
            $remote = $this->request('GET', '/order/' . $remoteId . '?include=status');
            if ((int) ($remote['id'] ?? 0) !== $remoteId || (int) ($remote['source_id'] ?? 0) !== 2
                || (string) ($remote['source_uuid'] ?? '') !== (string) $id) throw new RuntimeException('Order source identity mismatch.');
            $fresh = wc_get_order($id);
            if (! $fresh || $fresh->get_status() !== $status || $fresh->get_meta(self::PENDING) !== $status) return false;
            $remoteStatus = (int) ($remote['status']['id'] ?? $remote['status_id'] ?? 0);
            if ($remoteStatus !== $target) {
                // Never reopen a CRM terminal order automatically from an active Woo stage.
                if (in_array((int) ($remote['status']['group_id'] ?? 0), [5, 6], true) && ! self::terminal($status)) {
                    throw new RuntimeException('CRM order is terminal; reopening requires review.');
                }
                $this->request('PUT', '/order/' . $remoteId, ['status_id' => $target]);
                $verified = $this->request('GET', '/order/' . $remoteId . '?include=status');
                if ((int) ($verified['status']['id'] ?? $verified['status_id'] ?? 0) !== $target) throw new RuntimeException('KeyCRM status readback did not match.');
            }
            $fresh = wc_get_order($id);
            if ($fresh && $fresh->get_status() === $status && $fresh->get_meta(self::PENDING) === $status) {
                $fresh->delete_meta_data(self::PENDING);
                $fresh->delete_meta_data(self::ERROR);
                $fresh->update_meta_data('_maruderm_keycrm_status_id', $target);
                $fresh->update_meta_data('_maruderm_keycrm_status_sync_origin', 'woocommerce');
                $fresh->save_meta_data();
            }
            return true;
        } catch (Throwable $error) {
            $order = wc_get_order($id);
            if ($order) {
                if ($order->get_meta(self::ERROR) !== $error->getMessage()) $order->add_order_note('KeyCRM status sync pending: ' . $error->getMessage());
                $order->update_meta_data(self::ERROR, $error->getMessage());
                $order->save_meta_data();
            }
            wc_get_logger()->warning($error->getMessage(), ['source' => 'maruderm-keycrm-outbound-status', 'order_id' => $id]);
            return false;
        } finally {
            self::unlock($id, $lock);
        }
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $settings = get_option('woocommerce_integration-keycrm_settings', []);
        if (empty($settings['api_key'])) throw new RuntimeException('KeyCRM token unavailable.');
        $wait = 1.1 - (microtime(true) - self::$lastRequest);
        if ($wait > 0) usleep((int) ceil($wait * 1000000));
        self::$lastRequest = microtime(true);
        $args = ['method' => $method, 'timeout' => 15, 'headers' => [
            'Authorization' => 'Bearer ' . $settings['api_key'], 'Accept' => 'application/json', 'Content-Type' => 'application/json']];
        if ($body) $args['body'] = wp_json_encode($body);
        $response = wp_remote_request('https://openapi.keycrm.app/v1' . $path, $args);
        if (is_wp_error($response)) throw new RuntimeException('KeyCRM network request failed.');
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || ! is_array($data)) throw new RuntimeException('KeyCRM request rejected (HTTP ' . $code . ').');
        return $data;
    }

    public static function lock(int $id): string
    {
        $key = 'maruderm_status_sync_lock_' . $id;
        $old = get_option($key);
        if (is_string($old) && (int) explode(':', $old)[0] < time() - 90) self::unlock($id, $old);
        $token = time() . ':' . wp_generate_uuid4();
        return add_option($key, $token, '', false) ? $token : '';
    }

    public static function unlock(int $id, string $token): void
    {
        global $wpdb;
        $key = 'maruderm_status_sync_lock_' . $id;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $key, $token));
        wp_cache_delete($key, 'options');
    }
}

(new Maruderm_KeyCRM_Order_Status_Sync())->register();
