<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class UpdateNpWarehouses_20260406002401 extends Migration
{
    public function name(): string
    {
        return 'update_np_warehouses_20260406002401';
    }

    public function up(\wpdb $db): void
    {
        $prefix = $db->prefix;

        $db->query("
            ALTER TABLE `{$prefix}wc_ukr_shipping_np_warehouses`
            ADD COLUMN `total_max_weight_allowed` int(10) unsigned NOT NULL DEFAULT 0 AFTER `city_ref`,
            ADD COLUMN `place_max_weight_allowed` int(10) unsigned NOT NULL DEFAULT 0 AFTER `total_max_weight_allowed`
        ");
    }
}
