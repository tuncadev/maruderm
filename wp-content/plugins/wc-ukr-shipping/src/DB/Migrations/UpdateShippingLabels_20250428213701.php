<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class UpdateShippingLabels_20250428213701 extends Migration
{
    public function name(): string
    {
        return 'update_shipping_labels_20250428213701';
    }

    public function up(\wpdb $db): void
    {
        $prefix = $db->prefix;

        $db->query("
            ALTER TABLE `{$prefix}wc_ukr_shipping_labels`
            MODIFY COLUMN `label_id` VARCHAR(191) NULL DEFAULT NULL,
            ADD COLUMN `carrier_label_id` VARCHAR(191) NULL DEFAULT NULL AFTER `label_id`,
            ADD UNIQUE INDEX `u_tracking_number` (`tracking_number`)
        ");
    }
}
