<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

class SmartyParcel implements ModuleInterface
{
    private SmartyParcelWPApi $api;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
    }

    public function init()
    {
        add_action('init', [$this, 'handleOauthCallback']);
    }

    public function handleOauthCallback(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wcus_oauth_callback') {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['access_token']) || !wp_verify_nonce($_GET['nonce'] ?? '', 'smartyparcel_oauth')) {
            return;
        }

        try {
            if (get_option(WCUS_OPTION_SMARTY_PARCEL_USER_STATUS) !== 'connected') {
                $response = $this->api->connectApplication(sanitize_text_field($_GET['access_token']));

                update_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY, $response['api_key']);
                update_option(WCUS_OPTION_SMARTY_PARCEL_USER_STATUS, 'connected');
            }

            wp_safe_redirect(admin_url('admin.php?page=wcus_smarty_parcel'));
            exit;
        } catch (\Exception $e) {
            echo 'Oauth failed. Please try again or contact the support team.';
            exit;
        }
    }
}
