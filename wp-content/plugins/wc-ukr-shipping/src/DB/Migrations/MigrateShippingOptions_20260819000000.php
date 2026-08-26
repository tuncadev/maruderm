<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUSCore\DB\Migration;

class MigrateShippingOptions_20260819000000 extends Migration
{
    private SmartyParcelWPApi $api;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
    }

    public function name(): string
    {
        return 'migrate_shipping_options_20260819000000';
    }

    public function up(\wpdb $db): void
    {
        // Skip and done migration if store not connected to SmartyParcel
        // Actual for new plugin installations
        if (!SmartyParcelHelper::isConnected()) {
            return;
        }

        try {
            $this->migrateSettings($this->buildSettingsPayload());
        } catch (\Throwable $e) {
            // soft skip if fails
        }
    }

    private function migrateSettings(array $settings): void
    {
        $this->api->sendRequest('/v1/legacy/settings/migrate', [
            'settings' => $settings,
        ]);
    }

    private function buildSettingsPayload(): array
    {
        $keys = [
            'wcus_cod_payment_id',
            'wcus_ttn_description',
            'wcus_ttn_weight_default',
            'wcus_ttn_width_default',
            'wcus_ttn_height_default',
            'wcus_ttn_length_default',
            'wc_ukr_shipping_np_ttn_payer_default',
            'wcus_np_payment_method_default',
            'wcus_ttn_pay_control_default',
            'wcus_ukrposhta_ttn_default_payer',
            'wcus_ukrposhta_on_fail_receive',
            'wcus_ukrposhta_check_on_delivery',
            'wcus_ukrposhta_sms_notification',
            'wcus_ukrposhta_cod_payer',
            'wcus_rozetka_ttn_default_payer',
        ];

        $payload = [];
        foreach ($keys as $key) {
            $payload[$key] = get_option($key, null);
        }

        return $payload;
    }
}
