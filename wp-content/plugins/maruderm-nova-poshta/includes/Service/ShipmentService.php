<?php

namespace MarudermNovaPoshta\Service;

use MarudermNovaPoshta\Api\Client;
use MarudermNovaPoshta\Config;
use RuntimeException;
use WC_Order;

class ShipmentService
{
    public function __construct(
        private Client $client,
        private Config $config,
        private DivisionService $divisionService
    ) {
    }

    public function maybeCreateShipmentForOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (! $order instanceof WC_Order) {
            return;
        }

        $divisionId = (int) $order->get_meta('_nova_poshta_division_id');
        $settlementId = (int) $order->get_meta('_nova_poshta_settlement_id');
        if ($divisionId <= 0 || $settlementId <= 0) {
            return;
        }

        if ((string) $order->get_meta('_nova_poshta_shipment_number') !== '') {
            return;
        }

        if ((string) $order->get_meta('_nova_poshta_shipment_lock') === '1') {
            return;
        }

        if (! $this->divisionService->isDivisionInSettlement($divisionId, $settlementId)) {
            $order->add_order_note('Nova Poshta: selected division is not valid for settlement.');
            return;
        }

        $senderDivisionId = $this->config->getSenderDivisionId();
        $senderName = $this->config->getSenderName();
        $senderPhone = $this->config->getSenderPhone();

        if ($senderDivisionId <= 0 || $senderName === '' || $senderPhone === '') {
            $order->add_order_note('Nova Poshta: sender defaults are missing in .env.');
            return;
        }

        $order->update_meta_data('_nova_poshta_shipment_lock', '1');
        $order->save();

        try {
            $payload = $this->buildShipmentPayload($order, $divisionId, $senderDivisionId, $senderName, $senderPhone);

            $calcPayload = [
                'payerType' => 'Recipient',
                'parcels' => $payload['parcels'],
                'sender' => [
                    'countryCode' => 'UA',
                    'divisionId' => $senderDivisionId,
                ],
                'recipient' => [
                    'countryCode' => 'UA',
                    'divisionId' => $divisionId,
                ],
            ];

            $calculation = $this->client->post('/shipments/calculations', $calcPayload);
            $order->update_meta_data('_nova_poshta_calculation_response', wp_json_encode($calculation));

            $response = $this->client->post('/shipments', $payload);

            $shipmentId = (string) ($response['id'] ?? '');
            $shipmentNumber = (string) ($response['number'] ?? '');

            if ($shipmentNumber === '') {
                throw new RuntimeException('Shipment was created without a shipment number.');
            }

            $order->update_meta_data('_nova_poshta_shipment_id', $shipmentId);
            $order->update_meta_data('_nova_poshta_shipment_number', $shipmentNumber);
            $order->update_meta_data('_nova_poshta_shipment_status', (string) ($response['status'] ?? ''));
            $order->update_meta_data('_nova_poshta_shipment_request', wp_json_encode($payload));
            $order->update_meta_data('_nova_poshta_shipment_response', wp_json_encode($response));
            $order->add_order_note('Nova Poshta TTN created: ' . $shipmentNumber);
        } catch (\Throwable $exception) {
            $order->add_order_note('Nova Poshta TTN error: ' . $exception->getMessage());
        }

        $order->update_meta_data('_nova_poshta_shipment_lock', '0');
        $order->save();
    }

    public function recreateShipmentForOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (! $order instanceof WC_Order) {
            return;
        }

        $order->delete_meta_data('_nova_poshta_shipment_id');
        $order->delete_meta_data('_nova_poshta_shipment_number');
        $order->delete_meta_data('_nova_poshta_shipment_status');
        $order->delete_meta_data('_nova_poshta_shipment_request');
        $order->delete_meta_data('_nova_poshta_shipment_response');
        $order->save();

        $this->maybeCreateShipmentForOrder($orderId);
    }

    public function cancelShipmentForOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (! $order instanceof WC_Order) {
            return;
        }

        $shipmentId = (string) $order->get_meta('_nova_poshta_shipment_id');
        if ($shipmentId === '') {
            $order->add_order_note('Nova Poshta: no shipment id to cancel.');
            return;
        }

        try {
            $this->client->delete('/shipments/' . rawurlencode($shipmentId));
            $order->update_meta_data('_nova_poshta_shipment_status', 'Canceled');
            $order->add_order_note('Nova Poshta TTN canceled. Shipment ID: ' . $shipmentId);
            $order->save();
        } catch (\Throwable $exception) {
            $order->add_order_note('Nova Poshta cancel error: ' . $exception->getMessage());
        }
    }

    public function syncTrackingForOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (! $order instanceof WC_Order) {
            return;
        }

        $shipmentNumber = (string) $order->get_meta('_nova_poshta_shipment_number');
        if ($shipmentNumber === '') {
            $order->add_order_note('Nova Poshta: no shipment number for tracking.');
            return;
        }

        try {
            $response = $this->client->get('/shipments/tracking', [
                'numbers' => [$shipmentNumber],
                'countryCode' => 'UA',
            ]);

            $status = $this->extractStatusFromTrackingResponse($response);
            if ($status !== '') {
                $order->update_meta_data('_nova_poshta_shipment_status', $status);
            }
            $order->update_meta_data('_nova_poshta_last_tracking_payload', wp_json_encode($response));
            $order->add_order_note('Nova Poshta tracking synced: ' . ($status !== '' ? $status : 'updated'));
            $order->save();
        } catch (\Throwable $exception) {
            $order->add_order_note('Nova Poshta tracking sync error: ' . $exception->getMessage());
        }
    }

    public function requestPrintForOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (! $order instanceof WC_Order) {
            return;
        }

        $shipmentNumber = (string) $order->get_meta('_nova_poshta_shipment_number');
        if ($shipmentNumber === '') {
            $order->add_order_note('Nova Poshta: no shipment number for print.');
            return;
        }

        try {
            $response = $this->client->get('/shipments/print', [
                'numbers' => [$shipmentNumber],
                'type' => ['marking'],
                'printSizeType' => ['size_A4'],
                'deliveryType' => 'Shipment',
            ]);

            $order->update_meta_data('_nova_poshta_last_print_payload', wp_json_encode($response));
            $order->add_order_note('Nova Poshta print request executed for TTN: ' . $shipmentNumber);
            $order->save();
        } catch (\Throwable $exception) {
            $order->add_order_note('Nova Poshta print error: ' . $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildShipmentPayload(
        WC_Order $order,
        int $recipientDivisionId,
        int $senderDivisionId,
        string $senderName,
        string $senderPhone
    ): array {
        $recipientName = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
        if ($recipientName === '') {
            $recipientName = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        }

        $recipientPhone = (string) $order->get_billing_phone();
        $insuranceCost = (float) $order->get_total();
        $actualWeight = $this->getOrderWeight($order);

        return [
            'clientOrder' => (string) $order->get_order_number(),
            'deliveryType' => 'Shipment',
            'payerType' => 'Recipient',
            'note' => 'Order #' . $order->get_order_number(),
            'parcels' => [
                [
                    'cargoCategory' => 'parcel',
                    'parcelDescription' => 'Order #' . $order->get_order_number(),
                    'insuranceCost' => $insuranceCost,
                    'rowNumber' => 1,
                    'width' => $this->config->getDefaultParcelWidth(),
                    'length' => $this->config->getDefaultParcelLength(),
                    'height' => $this->config->getDefaultParcelHeight(),
                    'actualWeight' => $actualWeight,
                ],
            ],
            'sender' => [
                'name' => $senderName,
                'phone' => $senderPhone,
                'countryCode' => 'UA',
                'divisionID' => $senderDivisionId,
            ],
            'recipient' => [
                'name' => $recipientName,
                'phone' => $recipientPhone,
                'countryCode' => 'UA',
                'divisionID' => $recipientDivisionId,
            ],
        ];
    }

    private function getOrderWeight(WC_Order $order): int
    {
        $total = 0.0;

        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            if (! $product) {
                continue;
            }

            $weight = (float) $product->get_weight();
            $qty = (int) $item->get_quantity();
            $total += ($weight > 0 ? $weight : 0.2) * max(1, $qty);
        }

        if ($total <= 0) {
            $total = 1.0;
        }

        return (int) max(1, ceil($total));
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractStatusFromTrackingResponse(array $response): string
    {
        $items = $response['items'] ?? null;
        if (! is_array($items) || $items === []) {
            return '';
        }

        $first = $items[0] ?? null;
        if (! is_array($first)) {
            return '';
        }

        $candidates = ['status', 'statusName', 'status_name', 'eventType', 'event_type'];
        foreach ($candidates as $candidate) {
            if (isset($first[$candidate]) && is_string($first[$candidate]) && $first[$candidate] !== '') {
                return $first[$candidate];
            }
        }

        return '';
    }
}
