<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\SmartyParcel\ManifestService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;
use kirillbdev\WCUkrShipping\Services\TranslateService;
use kirillbdev\WCUkrShipping\Traits\StateInitiatorTrait;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class AssetsLoader implements ModuleInterface
{
    use StateInitiatorTrait;

    private TranslateService $translateService;
    private SmartyParcelService $smartyParcelService;
    private ManifestService $manifestService;

    public function __construct(
        TranslateService $translateService,
        SmartyParcelService $smartyParcelService,
        ManifestService $manifestService
    ) {
        $this->translateService = $translateService;
        $this->smartyParcelService = $smartyParcelService;
        $this->manifestService = $manifestService;
    }

    public function init()
    {
        add_action('admin_enqueue_scripts', [$this, 'loadAdminAssets']);
        add_action('admin_head', [ $this, 'initState' ]);
    }

    public function loadAdminAssets()
    {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');

        wp_enqueue_style(
            'wc_ukr_shipping_admin_css',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/css/admin.min.css',
            [],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/css/admin.min.css')
        );

        wp_enqueue_script(
            'wc_ukr_shipping_tabs_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/tabs.js',
            [],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/tabs.js')
        );

        if (get_current_screen() !== null && get_current_screen()->id === 'toplevel_page_wc_ukr_shipping_options') {
            wp_enqueue_script(
                'wcus_settings_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/settings.min.js',
                ['jquery'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/settings.min.js'),
                true
            );
        }

        $manifest = $this->manifestService->getManifest();
        if (isset($manifest['static']['elements-sdk'])) {
            wp_enqueue_script(
                'smartyparcel_elements_sdk_js',
                $manifest['static']['elements-sdk']['href'],
                [],
                $manifest['static']['elements-sdk']['version'],
                true
            );
        }

        // Common admin scripts
        wp_enqueue_script(
            'wcus_ttn_widget_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/ttn-widget.min.js',
            ['jquery'],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/ttn-widget.min.js'),
            true
        );

        wp_enqueue_style(
            'smarty_parcel_admin_css',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/css/sp-admin.min.css',
            [],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/css/sp-admin.min.css')
        );

        wp_enqueue_script(
            'smarty_parcel_admin_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/sp-admin.min.js',
            ['jquery'],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/sp-admin.min.js'),
            true
        );

        if (get_current_screen() !== null && get_current_screen()->id === 'plugins') {
            wp_enqueue_script(
                'wcus_plugin_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/plugin.min.js',
                ['jquery'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/plugin.min.js'),
                true
            );
        }

        if (get_current_screen() !== null && get_current_screen()->id === 'wc-ukr-shipping_page_wc_ukr_shipping_ttn_list') {
            wp_enqueue_script(
                'wcus_orders_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/orders.min.js',
                ['jquery'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/orders.min.js'),
                true
            );
        }

        if (get_current_screen() !== null && in_array(get_current_screen()->id, ['admin_page_wcus_automation_rule_create', 'admin_page_wcus_automation_rule_edit'], true)) {
            wp_enqueue_script(
                'wcus_automation_form_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/automation.min.js',
                ['jquery'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/automation.min.js'),
                true
            );
        }

        if (get_current_screen() !== null && get_current_screen()->id === 'wc-ukr-shipping_page_wc_ukr_shipping_tools') {
            wp_enqueue_script(
                'wcus_tools_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/tools.min.js',
                ['jquery'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/tools.min.js'),
                true
            );
        }

        $this->injectGlobals('jquery');
        $this->injectTranslates('jquery');
    }

    private function injectGlobals($scriptId): void
    {
        $translator = $this->translateService;
        $globals = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url(),
            'adminUrl' => admin_url(),
            'lang' => $translator->getCurrentLanguage(),
            'nonce' => wp_create_nonce('wc-ukr-shipping'),
            'disableDefaultBillingFields' => apply_filters('wc_ukr_shipping_prevent_disable_default_fields', false) === false ?
                'true' :
                'false',
            'orderStatuses' => wcus_is_woocommerce_active() ? wc_get_order_statuses() : [],
            'assets' => [
                'nova_poshta_icon_url' => WC_UKR_SHIPPING_PLUGIN_URL . 'image/nova-poshta-icon.png',
            ],
            'default_cities' => $this->getDefaultCities(),
            'ukrposhta' => [
                'defaultCities' => WCUSHelper::getUkrposhtaDefaultCities(),
            ],
            'rozetkaDelivery' => [
                'defaultCities' => WCUSHelper::getRozetkaDefaultCities(),
            ],
            'feedBackAllowed' => get_transient('wcus_last_feedback') ? 0 : 1,
        ];
        $globals = $this->initSmartyParcelGlobals($globals);

        $i18n = [];
        $globals['i18n'] = apply_filters('wcus_load_admin_i18n', $i18n);
        wp_localize_script($scriptId, 'wc_ukr_shipping_globals', $globals);
    }

    private function initSmartyParcelGlobals(array $globals): array
    {
        $connectUrl = null;
        if (!SmartyParcelHelper::isConnected()) {
            $sessionData = urlencode(base64_encode(json_encode([
                'storeUrl' => rtrim(site_url(), '/'),
                'callbackUrl' => admin_url('admin.php?page=wcus_oauth_callback'),
                'platform' => 'woocommerce',
                'nonce' => wp_create_nonce('smartyparcel_oauth'),
            ])));
            $connectUrl = 'https://app.smartyparcel.com/oauth-session?session_data=' . $sessionData;
        }

        $globals['smarty_parcel'] = [
            'isConnected' => SmartyParcelHelper::isConnected(),
            'connectUrl' => $connectUrl,
            'account' => null,
            'dashboardUrl' =>  admin_url('admin.php?page=wcus_smarty_parcel'),
            'upgradePlanUrl' => admin_url('admin.php?page=wcus_smarty_parcel#/upgrade'),
        ];

        $needAccountScreens = [
            'toplevel_page_wc_ukr_shipping_options',
            'wc-ukr-shipping_page_wc_ukr_shipping_ttn_list',
        ];
        if (get_current_screen() !== null && in_array(get_current_screen()->id, $needAccountScreens)) {
            $globals['smarty_parcel']['account'] = $this->smartyParcelService->getAccountInfo();
        }

        return $globals;
    }

    private function injectTranslates($scriptId): void
    {
        $translates = file_get_contents(WC_UKR_SHIPPING_PLUGIN_DIR . 'lang/frontend.json');
        if ($translates === false) {
            return;
        }

        $json = json_decode($translates, true);
        if (json_last_error()) {
            return;
        }

        $translations = [];
        foreach ($json['messages'] ?? [] as $message) {
            $translations[$message] = __($message, $json['domain']);
        }

        wp_localize_script($scriptId, 'wc_ukr_shipping_i18n', $translations);
    }

    private function getDefaultCities(): array
    {
        $locale = $this->translateService->getCurrentLanguage();

        return array_map(function($item) use($locale) {
            return [
                'name' => $item[$locale === 'ua' ? 'description' : 'description_ru'],
                'value' => $item['ref']
            ];
        }, WCUSHelper::getDefaultCities());
    }
}
