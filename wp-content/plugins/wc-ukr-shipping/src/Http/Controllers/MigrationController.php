<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUSCore\DB\Migrator;
use kirillbdev\WCUSCore\Exceptions\MigrateException;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

class MigrationController extends Controller
{
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function reRunMigrations(Request $request)
    {
        try {
            $this->migrator->reRun();

            return $this->jsonResponse([
                'success' => true,
                'message' => __('Migrations were successfully applied', 'wc-ukr-shipping-i18n'),
            ]);
        } catch (MigrateException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error_message' => sprintf(
                    '%s %s',
                    __('Unable to apply migrations', 'wc-ukr-shipping-i18n'),
                    $e->getMessage()
                ),
            ]);
        }
    }
}
