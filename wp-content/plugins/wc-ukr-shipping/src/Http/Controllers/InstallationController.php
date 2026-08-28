<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class InstallationController extends Controller
{
    private const ATTEMPT_TRANSIENT = 'wcus_installation_register_attempt';

    private SmartyParcelWPApi $api;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
    }

    /**
     * Registers the current installation on the platform and stores the returned id.
     * Any failure is silent: telemetry must never surface errors to the store admin.
     */
    public function register(Request $request)
    {
        if (get_option(WCUS_OPTION_INSTALLATION_ID) || get_transient(self::ATTEMPT_TRANSIENT)) {
            return $this->jsonResponse(['success' => true]);
        }

        // Do not hammer the API when it's unavailable
        set_transient(self::ATTEMPT_TRANSIENT, time(), 900);

        try {
            $meta = get_file_data(WC_UKR_SHIPPING_PLUGIN_ENTRY, ['Version' => 'Version']);
            $response = $this->api->sendRequest('/v1/app/register', [
                'store_url' => rtrim(site_url(), '/'),
                'app_name' => 'wc-ukr-shipping',
                'app_version' => $meta['Version'] ?? 'undefined',
                'locale' => get_locale(),
                'currency' => wcus_is_woocommerce_active() ? get_woocommerce_currency() : '',
                'wc_base_country' => wcus_is_woocommerce_active() ? WC()->countries->get_base_country() : '',
                'admin_email' => get_option('admin_email'),
            ]);

            if ( ! empty($response['installation_id'])) {
                update_option(WCUS_OPTION_INSTALLATION_ID, $response['installation_id'], false);
                delete_transient(self::ATTEMPT_TRANSIENT);
            }
        } catch (\Throwable $e) {
            // safe
        }

        return $this->jsonResponse(['success' => true]);
    }
}
