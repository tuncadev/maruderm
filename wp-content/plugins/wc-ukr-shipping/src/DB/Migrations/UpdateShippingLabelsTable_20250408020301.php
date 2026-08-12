<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class UpdateShippingLabelsTable_20250408020301 extends Migration
{
    public function name(): string
    {
        return 'update_shipping_labels_20250408020301';
    }

    public function up(\wpdb $db): void
    {
        $prefix = $db->prefix;

        $db->query("
            ALTER TABLE `{$prefix}wc_ukr_shipping_labels`
            ADD COLUMN `tracking_active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tracking_number`,
            ADD COLUMN `tracking_sub_status` VARCHAR(255) NULL AFTER `tracking_status`,
            ADD INDEX `i_tracking_active` (`tracking_active`, `created_at`)
        ");
    }
}
