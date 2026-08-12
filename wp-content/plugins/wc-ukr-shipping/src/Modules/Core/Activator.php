<?php

namespace kirillbdev\WCUkrShipping\Modules\Core;

use kirillbdev\WCUkrShipping\DB\Migrations\CreateAutomationActionsTable_20240923215754;
use kirillbdev\WCUkrShipping\DB\Migrations\CreateAutomationRulesTable_20240923215722;
use kirillbdev\WCUkrShipping\DB\Migrations\CreateShippingLabelsTable_20250203230634;
use kirillbdev\WCUkrShipping\DB\Migrations\UpdateNpWarehouses_20260406002401;
use kirillbdev\WCUkrShipping\DB\Migrations\UpdateShippingLabels_20250428213701;
use kirillbdev\WCUkrShipping\DB\Migrations\UpdateShippingLabels_20251008164100;
use kirillbdev\WCUkrShipping\DB\Migrations\UpdateShippingLabelsTable_20250408020301;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\DB\Migrator;
use kirillbdev\WCUSCore\Exceptions\MigrateException;

class Activator implements ModuleInterface
{
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function init(): void
    {
        add_action('plugins_loaded', [$this, 'activate']);
        register_activation_hook(WC_UKR_SHIPPING_PLUGIN_ENTRY, [$this, 'activate']);
        register_deactivation_hook(WC_UKR_SHIPPING_PLUGIN_ENTRY, [$this, 'deactivate']);
    }

    public function activate(): void
    {
        try {
            // All base tables (both in lite and pro versions) are added in core package
            $this->migrator->addMigration(new CreateShippingLabelsTable_20250203230634());
            $this->migrator->addMigration(new UpdateShippingLabelsTable_20250408020301());
            $this->migrator->addMigration(new CreateAutomationActionsTable_20240923215754());
            $this->migrator->addMigration(new CreateAutomationRulesTable_20240923215722());
            $this->migrator->addMigration(new UpdateShippingLabels_20250428213701());
            $this->migrator->addMigration(new UpdateShippingLabels_20251008164100());
            $this->migrator->addMigration(new UpdateNpWarehouses_20260406002401());
            $this->migrator->run();
        } catch (MigrateException $e) {
            // do nothing yet
        }
    }

    public function deactivate(): void
    {
        // Clear jobs
        wp_unschedule_hook('wcus_tracking_worker_tick');
        delete_option('wc_ukr_shipping_workers_version');
    }
}
