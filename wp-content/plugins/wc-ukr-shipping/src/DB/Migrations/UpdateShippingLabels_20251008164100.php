<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class UpdateShippingLabels_20251008164100 extends Migration
{
    public function name(): string
    {
        return 'update_shipping_labels_20251008164100';
    }

    public function up(\wpdb $db): void
    {
        $prefix = $db->prefix;

        $db->query("
            ALTER TABLE `{$prefix}wc_ukr_shipping_labels`
            ADD COLUMN `metadata` TEXT DEFAULT NULL AFTER `carrier_status_code`
        ");
    }
}
