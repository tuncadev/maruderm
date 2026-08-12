<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUSCore\Facades\DB;

class ShippingLabelsRepository
{
    public function findByOrderId(int $orderId): ?array
    {
        $row = DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->where('order_id', $orderId)
            ->first();

        return $this->hydrate($row);
    }

    public function findById(int $id): ?array
    {
        $row = DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->where('id', $id)
            ->first();

        return $this->hydrate($row);
    }

    public function findByTrackingNumber(string $trackingNumber): ?array
    {
        $row = DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->where('tracking_number', $trackingNumber)
            ->first();

        return $this->hydrate($row);
    }

    public function deleteById(int $id): void
    {
        global $wpdb;
        $wpdb->delete(DB::prefixedTable('wc_ukr_shipping_labels'), [
            'id' => $id
        ], [
            'id' => '%d'
        ]);
    }

    public function create(
        int $orderId,
        string $labelId,
        string $carrierLabelId,
        string $trackingNumber,
        string $carrierSlug,
        array $metadata = []
    ) {
        $now = date('Y-m-d H:i:s');
        $values = [
            'label_id' => $labelId,
            'carrier_label_id' => $carrierLabelId,
            'carrier_slug' => $carrierSlug,
            'order_id' => $orderId,
            'tracking_number' => $trackingNumber,
            'tracking_active' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (count($metadata) > 0) {
            $values['metadata'] = json_encode($metadata);
        }

        DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->insert($values);
    }

    public function attach(int $orderId, string $trackingNumber, string $carrierSlug): void
    {
        $now = date('Y-m-d H:i:s');
        DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->insert([
                'carrier_slug' => $carrierSlug,
                'order_id' => $orderId,
                'tracking_number' => $trackingNumber,
                'tracking_active' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function addToTracking(int $id): void
    {
        global $wpdb;
        $wpdb->update(
            DB::prefixedTable('wc_ukr_shipping_labels'),
            [
                'tracking_active' => 1,
                'tracking_status' => 'PENDING',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [ 'id' => $id ],
            [ '%d', '%s', '%s' ],
            [ '%d' ]
        );
    }

    private function hydrate($row): ?array
    {
        if ($row === null) {
            return null;
        }

        $result = (array)$row;
        if ($result['metadata']) {
            $result['metadata'] = json_decode($result['metadata'], true);
        }

        return $result;
    }
}
