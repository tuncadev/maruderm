<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class CreateShippingLabelsTable_20250203230634 extends Migration
{
    public function name(): string
    {
        return 'create_shipping_labels_20250203230634';
    }

    public function up(\wpdb $db): void
    {
        $collate = $db->get_charset_collate();
        $prefix = $db->prefix;

        $db->query("
            CREATE TABLE IF NOT EXISTS `{$prefix}wc_ukr_shipping_labels` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `label_id` varchar(191) NOT NULL,
                `carrier_slug` varchar(50) DEFAULT NULL,
                `order_id` int(10) NOT NULL DEFAULT '0',
                `tracking_number` varchar(191) NOT NULL,
                `tracking_status` varchar(100) DEFAULT NULL,
                `carrier_status` varchar(255) DEFAULT NULL,
                `carrier_status_code` varchar(255) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `i_order` (`order_id`),
                UNIQUE KEY `u_label` (`label_id`)
            ) ENGINE=InnoDB $collate
        ");
    }
}
