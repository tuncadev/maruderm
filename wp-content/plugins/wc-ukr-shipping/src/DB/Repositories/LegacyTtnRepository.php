<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUSCore\Facades\DB;

class LegacyTtnRepository
{
    public function getCountTtn(): int
    {
        global $wpdb;

        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}wc_ukr_shipping_np_ttn'", ARRAY_A);
        if (!is_array($tables) || count($tables) === 0) {
            return 0;
        }

        return DB::table(DB::prefixedTable('wc_ukr_shipping_np_ttn'))
            ->where('created_at', '>', (new \DateTime())->sub(new \DateInterval('P3M'))->format('Y-m-d'))
            ->count();
    }

    public function syncTtn(int $limit, int $lastId = 0): array
    {
        global $wpdb;

        if ($lastId === 0) {
            $wpdb->query(
                "DELETE FROM `{$wpdb->prefix}wc_ukr_shipping_labels` WHERE `carrier_slug` = 'wcus_pro'"
            );
            if ($wpdb->last_error) {
                throw new \Exception('QueryException: ' . $wpdb->last_error);
            }
        }

        $items = DB::table(DB::prefixedTable('wc_ukr_shipping_np_ttn'))
            ->where('created_at', '>', (new \DateTime())->sub(new \DateInterval('P3M'))->format('Y-m-d'))
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($wpdb->last_error) {
            throw new \Exception('QueryException: ' . $wpdb->last_error);
        }

        $now = date('Y-m-d H:i:s');
        foreach ($items as $item) {
            DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
                ->insert([
                    'carrier_label_id' => $item['ttn_ref'],
                    'carrier_slug' => 'wcus_pro',
                    'order_id' => $item['order_id'],
                    'tracking_number' => $item['ttn_id'],
                    'tracking_active' => 0,
                    'tracking_status' => $item['cloud_status'] ?? null,
                    'carrier_status' => $item['status'] ?? null,
                    'carrier_status_code' => $item['status_code'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($wpdb->last_error && stripos($wpdb->last_error, 'duplicate entry') === false) {
                throw new \Exception('QueryException: ' . $wpdb->last_error);
            }
        }

        return [
            'synced' => count($items),
            'total' => $this->getCountTtn(),
            'lastId' => count($items) > 0 ? max(array_column($items, 'id')) : 0,
        ];
    }
}
