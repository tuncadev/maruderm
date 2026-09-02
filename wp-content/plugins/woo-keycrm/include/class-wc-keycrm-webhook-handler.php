<?php
/**
 * KeyCRM Webhook Handler
 *
 * @package   WC_Keycrm
 * @category  Webhook
 * @author    KeyCRM
 * @since     1.0.21
 */

if (!class_exists('WC_Keycrm_Webhook_Handler')) :

    class WC_Keycrm_Webhook_Handler
    {
        /**
         * KeyCRM → WooCommerce status mapping
         *
         * @var array
         */
        private $status_mapping = array(
            1 => 'pending',
            2 => 'processing',
            3 => 'processing',
            4 => 'processing',
            5 => 'completed',
            6 => 'cancelled',
        );

        /**
         * Process incoming webhook data
         *
         * @param array|mixed $data Incoming webhook payload
         * @return void
         */
        public function process($data)
        {
            WC_Keycrm_Logger::debug(__METHOD__, array('webhook' => $data));

            // Get plugin settings
            $settings = get_option('woocommerce_integration-keycrm_settings', array());

            if (empty($data['event'])) {
                if (isset($settings['webhook_stock_update']) &&
                    $settings['webhook_stock_update'] === 'yes') {
                    if (isset($data[0]) && is_array($data[0]) &&
                        (isset($data[0]['sku']) || isset($data[0]['offer_id']))) {
                        $this->update_product_stock($data);
                    }
                }
                return;
            }

            if ($data['event'] === 'order.change_order_status') {
                if (!empty($data['context']) &&
                    isset($settings['webhook_order_status_update']) &&
                    $settings['webhook_order_status_update'] === 'yes') {
                    $this->update_order_status($data['context']);
                }
            }
        }

        /**
         * Update product stock quantities based on SKU
         *
         * @param array $stock_data Stock data from KeyCRM
         * @return void
         */
        private function update_product_stock($stock_data)
        {
            if (!is_array($stock_data)) {
                WC_Keycrm_Logger::add('Webhook: Invalid stock data format');
                return;
            }

            $updated_count = 0;
            $failed_count = 0;

            foreach ($stock_data as $item) {
                if (empty($item['sku'])) {
                    WC_Keycrm_Logger::debug('Webhook: Missing SKU in stock data', $item);
                    $failed_count++;
                    continue;
                }

                $product_id = $this->get_product_id_by_sku($item['sku']);

                if (!$product_id) {
                    WC_Keycrm_Logger::debug(__METHOD__,'Webhook: Product not found by SKU: ' . $item['sku']);
                    $failed_count++;
                    continue;
                }

                $product = wc_get_product($product_id);

                if (!$product) {
                    WC_Keycrm_Logger::debug(__METHOD__,'Webhook: Cannot load product ID: ' . $product_id);
                    $failed_count++;
                    continue;
                }

                $available_stock = isset($item['in_stock']) ? (int)$item['in_stock'] : 0;

                if ($product->get_manage_stock()) {
                    // Calculate actual stock (in_stock - in_reserve)
                    $reserved_stock = isset($item['in_reserve']) ? (int)$item['in_reserve'] : 0;
                    $actual_stock = $available_stock - $reserved_stock;

                    // Ensure stock is not negative
                    $actual_stock = max(0, $actual_stock);

                    // Update stock
                    wc_update_product_stock($product_id, $actual_stock);


                    $product->save();

                    WC_Keycrm_Logger::debug('Webhook: Updated stock for product', array(
                        'sku' => $item['sku'],
                        'product_id' => $product_id,
                        'in_stock' => $available_stock,
                        'in_reserve' => $reserved_stock,
                        'actual_stock' => $actual_stock
                    ));

                    $updated_count++;
                } else {
                    WC_Keycrm_Logger::debug(__METHOD__,'Webhook: Product does not manage stock: ' . $item['sku']);
                    $failed_count++;
                }
            }

            WC_Keycrm_Logger::add(sprintf(
                'Webhook: Stock update completed. Updated: %d, Failed: %d',
                $updated_count,
                $failed_count
            ));
        }

        /**
         * Get product ID by SKU
         *
         * @param string $sku Product SKU
         * @return int|null Product ID or null if not found
         */
        private function get_product_id_by_sku($sku)
        {
            global $wpdb;

            $product_id = $wpdb->get_var($wpdb->prepare("
                SELECT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_sku'
                AND meta_value = %s
                LIMIT 1
            ", $sku));

            if ($product_id) {
                return (int)$product_id;
            }

            // Also check for variation SKUs
            $variation_id = $wpdb->get_var($wpdb->prepare("
                SELECT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product_variation'
                AND pm.meta_key = '_sku'
                AND pm.meta_value = %s
                LIMIT 1
            ", $sku));

            if ($variation_id) {
                return (int)$variation_id;
            }

            return null;
        }

        /**
         * Update WooCommerce order status silently
         *
         * @param array $order_data Order data from KeyCRM
         * @return void
         */
        private function update_order_status($order_data)
        {
            if (empty($order_data['source_uuid'])) {
                WC_Keycrm_Logger::add('Webhook: Missing source_uuid');
                return;
            }

            if (!isset($order_data['status_group_id'])) {
                WC_Keycrm_Logger::add(
                    'Webhook: Missing status_group_id for order ' . $order_data['source_uuid']
                );
                return;
            }

            $order_id = (int) $order_data['source_uuid'];
            $order    = wc_get_order($order_id);

            if (!$order) {
                WC_Keycrm_Logger::add('Webhook: Order not found ' . $order_id);
                return;
            }

            $new_status = $this->map_status((int) $order_data['status_group_id']);

            if (!$new_status) {
                WC_Keycrm_Logger::add(
                    'Webhook: Unknown KeyCRM status ID ' . $order_data['status_group_id']
                );
                return;
            }

            if ($order->get_status() === $new_status) {
                return;
            }

            $this->silent_update_order_status($order_id, $new_status);

            WC_Keycrm_Logger::add(
                "Webhook: Order {$order_id} status updated to '{$new_status}' (silent)"
            );
        }

        /**
         * Update order status directly in database without hooks
         *
         * Supports both HPOS and non-HPOS modes
         *
         * @param int    $order_id   WooCommerce order ID
         * @param string $new_status WooCommerce status (without wc- prefix)
         * @return void
         */
        private function silent_update_order_status($order_id, $new_status)
        {
            global $wpdb;

            $new_status  = sanitize_key($new_status);
            $post_status = 'wc-' . $new_status;

            $hpos_enabled = (
                class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
                \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
            );

            if ($hpos_enabled) {

                $wpdb->update(
                    $wpdb->prefix . 'wc_orders',
                    array('status' => $new_status),
                    array('id' => $order_id),
                    array('%s'),
                    array('%d')
                );

                $wpdb->update(
                    $wpdb->posts,
                    array('post_status' => $post_status),
                    array('ID' => $order_id),
                    array('%s'),
                    array('%d')
                );

            } else {

                $wpdb->update(
                    $wpdb->posts,
                    array('post_status' => $post_status),
                    array('ID' => $order_id),
                    array('%s'),
                    array('%d')
                );
            }

            wp_cache_delete($order_id, 'posts');
            wp_cache_delete($order_id, 'orders');
        }

        /**
         * Map KeyCRM status ID to WooCommerce status
         *
         * @param int $keycrm_status_id KeyCRM status ID
         * @return string|null
         */
        private function map_status($keycrm_status_id)
        {
            return isset($this->status_mapping[$keycrm_status_id])
                ? $this->status_mapping[$keycrm_status_id]
                : null;
        }

        /**
         * Static method for REST API webhook handler
         */
        public static function handle_rest_webhook($request) {
            $secret = $request->get_param('secret');
            $settings = get_option('woocommerce_integration-keycrm_settings', array());
            $expected_secret = isset($settings['webhook_secret_key']) ? $settings['webhook_secret_key'] : '';

            if (empty($expected_secret) || $secret !== $expected_secret) {
                return new WP_REST_Response('Invalid secret', 401);
            }

            $data = $request->get_json_params();

            $handler = new self();
            $handler->process($data);

            return new WP_REST_Response('OK', 200);
        }
    }

endif;