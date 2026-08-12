<?php

namespace MarudermNovaPoshta\Service;

use MarudermNovaPoshta\Api\Client;
use MarudermNovaPoshta\Config;

class WebhookService
{
    public function __construct(
        private Client $client,
        private Config $config
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function processPayload(array $payload): void
    {
        $numbers = $this->extractShipmentNumbers($payload);
        if ($numbers === []) {
            return;
        }

        $statusText = $this->extractStatusText($payload);

        foreach ($numbers as $shipmentNumber) {
            $orders = wc_get_orders([
                'limit' => 10,
                'meta_key' => '_nova_poshta_shipment_number',
                'meta_value' => $shipmentNumber,
                'return' => 'objects',
            ]);

            foreach ($orders as $order) {
                if (! $order instanceof \WC_Order) {
                    continue;
                }

                $existingHash = (string) $order->get_meta('_nova_poshta_last_webhook_hash');
                $hash = hash('sha256', wp_json_encode($payload) . '|' . $shipmentNumber);
                if ($existingHash !== '' && hash_equals($existingHash, $hash)) {
                    continue;
                }

                $order->update_meta_data('_nova_poshta_last_tracking_payload', wp_json_encode($payload));
                $order->update_meta_data('_nova_poshta_shipment_status', $statusText);
                $order->update_meta_data('_nova_poshta_last_webhook_hash', $hash);
                $order->add_order_note('Nova Poshta tracking update (' . $shipmentNumber . '): ' . $statusText);
                $order->save();
            }
        }
    }

    public function ensureSubscriber(): void
    {
        $token = $this->config->getWebhookSecretToken();
        $url = $this->config->getWebhookUrl();

        if ($token === '' || $url === '') {
            return;
        }

        $lockKey = 'maruderm_nova_poshta_subscriber_checked';
        if (get_transient($lockKey)) {
            return;
        }

        set_transient($lockKey, '1', HOUR_IN_SECONDS);

        try {
            $existing = $this->client->get('/tracking-push/subscribers');
            $items = isset($existing['items']) && is_array($existing['items']) ? $existing['items'] : [];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if ((string) ($item['url'] ?? '') === $url && (bool) ($item['isActive'] ?? true)) {
                    return;
                }
            }

            $this->client->post('/tracking-push/subscribers', [
                'type' => 'numbers',
                'url' => $url,
                'isActive' => true,
                'eventTypes' => ['ReadyToShip', 'Received', 'Returned'],
                'sendWarnings' => true,
                'secretToken' => $token,
                'secretTokenHeaderName' => $this->config->getWebhookSecretHeaderName(),
            ]);
        } catch (\Throwable $exception) {
            error_log('[Maruderm Nova Poshta] webhook subscriber sync failed: ' . $exception->getMessage());
        }
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function extractShipmentNumbers(mixed $value): array
    {
        $numbers = [];

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                if (is_string($key) && in_array($key, ['number', 'numbers', 'shipmentNumber', 'shipment_number', 'expressWaybill'], true)) {
                    if (is_string($nestedValue) && $nestedValue !== '') {
                        $numbers[] = $nestedValue;
                    } elseif (is_array($nestedValue)) {
                        foreach ($nestedValue as $number) {
                            if (is_string($number) && $number !== '') {
                                $numbers[] = $number;
                            }
                        }
                    }
                }

                $numbers = array_merge($numbers, $this->extractShipmentNumbers($nestedValue));
            }
        }

        $numbers = array_values(array_unique(array_filter(array_map('strval', $numbers))));

        return $numbers;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractStatusText(array $payload): string
    {
        $candidates = ['status', 'statusName', 'status_name', 'eventType', 'event_type'];

        foreach ($candidates as $field) {
            if (isset($payload[$field]) && is_string($payload[$field]) && $payload[$field] !== '') {
                return $payload[$field];
            }
        }

        return 'updated';
    }
}
