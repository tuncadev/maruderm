<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories\Orders;

use kirillbdev\WCUSCore\Facades\DB;

class HposOrderRepository implements OrderRepositoryInterface
{
    public function getOrdersWithTTN(int $offset, int $limit, array $filters = []): array
    {
        $query = DB::table(DB::prefixedTable('wc_orders') . ' as o')
            ->leftJoin(DB::prefixedTable('wc_ukr_shipping_labels') . ' as l', 'o.id = l.order_id')
            ->where('o.status', '!=', 'trash')
            ->where('o.type', '=', 'shop_order');

        if (!empty($filters['carrier_slug'])) {
            $query->where('l.carrier_slug', '=', $filters['carrier_slug']);
        }

        return $query->orderBy('o.id', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->get([
                'o.id',
                'o.date_created_gmt as created_at',
                'o.status',
                'l.label_id',
                'l.carrier_slug',
                'l.id as label_db_id',
                'l.tracking_number',
                'l.tracking_status',
                'l.carrier_status_code',
                'l.carrier_status',
            ]);
    }

    public function getOrderInfo(int $orderId): array
    {
        $order = wc_get_order($orderId);
        if ( ! $order) {
            return [];
        }

        // todo: refactor, add shared structure between two repos
        return [
            [
                'meta_key' => '_billing_last_name',
                'meta_value' => $order->get_billing_last_name(),
            ],
            [
                'meta_key' => '_billing_first_name',
                'meta_value' => $order->get_billing_first_name(),
            ],
            [
                'meta_key' => '_order_total',
                'meta_value' => $order->get_total(),
            ],
            [
                'meta_key' => '_smartyparcel_label_id',
                'meta_value' => $order->get_meta('_smartyparcel_label_id'),
            ]
        ];
    }

    public function getOrderShippingMethod(int $orderId): ?\stdClass
    {
        return DB::table(DB::woocommerceOrderItems())
            ->where('order_id', (int)$orderId)
            ->where('order_item_type', 'shipping')
            ->first([
                'order_item_name'
            ]);
    }

    public function getCountOrderPages(int $limit, array $filters = []): int
    {
        $query = DB::table(DB::prefixedTable('wc_orders') . ' as o')
            ->leftJoin(DB::prefixedTable('wc_ukr_shipping_labels') . ' as l', 'o.id = l.order_id')
            ->where('o.status', '!=', 'trash')
            ->where('o.type', '=', 'shop_order');

        if (!empty($filters['carrier_slug'])) {
            $query->where('l.carrier_slug', '=', $filters['carrier_slug']);
        }

        $pageCount = $query->count('o.id');

        return ceil($pageCount / $limit);
    }
}
