<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
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

    public function __construct(TranslateService $translateService)
    {
        $this->translateService = $translateService;
    }

    public function init()
    {
        add_action('wp_head', [ $this, 'loadCheckoutStyles' ]);
        add_action('wp_head', [ $this, 'initState' ]);
        add_action('wp_enqueue_scripts', [ $this, 'loadFrontendAssets' ]);
    }

    public function loadFrontendAssets()
    {
        if (!wc_ukr_shipping_is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'wc_ukr_shipping_css',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/css/style.min.css',
            [],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/css/style.min.css')
        );

        wp_enqueue_script(
            'wcus_checkout_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/checkout2.min.js',
            [ 'jquery' ],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/checkout2.min.js'),
            true
        );

        $this->injectGlobals();
    }

    public function loadCheckoutStyles()
    {
        if (!wc_ukr_shipping_is_checkout()) {
            return;
        }

        ?>
      <style>
          .wc-ukr-shipping-np-fields {
              padding: 1px 0;
          }

          .wcus-state-loading:after {
              border-color: <?php echo esc_html(get_option('wc_ukr_shipping_spinner_color', '#dddddd')); ?>;
              border-left-color: #fff;
          }
      </style>
        <?php
    }

    private function injectGlobals()
    {
        $translator = $this->translateService;
        $globals = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url(),
            'lang' => $translator->getCurrentLanguage(),
            'nonce' => wp_create_nonce('wc-ukr-shipping'),
            'disableDefaultBillingFields' => apply_filters('wc_ukr_shipping_prevent_disable_default_fields', false) === false
                ? 1
                : 0,
            'options' => [
                'address_shipping_enable' => (int)wc_ukr_shipping_get_option('wc_ukr_shipping_address_shipping'),
                'useOnlineDirectory' => (int)wc_ukr_shipping_get_option('wcus_np_use_online_directory') === 1,
                'combinePoshtomats' => (int)wc_ukr_shipping_get_option('wcus_combine_poshtomats') === 1,
            ]
        ];

        $globals['default_cities'] = $this->getDefaultCities();
        $globals['ukrposhta']['defaultCities'] = WCUSHelper::getUkrposhtaDefaultCities();
        $globals['rozetkaDelivery']['defaultCities'] = WCUSHelper::getRozetkaDefaultCities();
        $globals['i18n'] = [
            'fields_title' => __('Select shipping address', 'wc-ukr-shipping-i18n'),
            'shipping_type_warehouse' => __('To warehouse', 'wc-ukr-shipping-i18n'),
            'shipping_type_doors' => __('By courier', 'wc-ukr-shipping-i18n'),
            'shipping_type_poshtomat' => __('To poshtomat', 'wc-ukr-shipping-i18n'),
            'shipping_type_warehouse_poshtomat' => __('To warehouse or poshtomat', 'wc-ukr-shipping-i18n'),
            'ui' => [
                'city_placeholder' => __('Select locality', 'wc-ukr-shipping-i18n'),
                'warehouse_placeholder' => __('Select warehouse', 'wc-ukr-shipping-i18n'),
                'poshtomat_placeholder' => __('Select poshtomat', 'wc-ukr-shipping-i18n'),
                'warehouse_poshtomat_placeholder' => __('Select warehouse or poshtomat', 'wc-ukr-shipping-i18n'),
                'custom_address_placeholder' => __('Enter address', 'wc-ukr-shipping-i18n'),
                'text_search' => __('Enter 3 or more characters to search', 'wc-ukr-shipping-i18n'),
                'text_search_warehouse' => __('Enter number or address of warehouse', 'wc-ukr-shipping-i18n'),
                'text_loading' => __('Loading...', 'wc-ukr-shipping-i18n'),
                'text_more' => __('Load more', 'wc-ukr-shipping-i18n'),
                'text_not_found' => __('Nothing found', 'wc-ukr-shipping-i18n'),
                'text_more_chars' => __('Enter more chars', 'wc-ukr-shipping-i18n'),
                'settlement_placeholder' => __('Select locality', 'wc-ukr-shipping-i18n'),
                'street_placeholder' => __('Select street', 'wc-ukr-shipping-i18n'),
                'house_placeholder' => __('Enter house', 'wc-ukr-shipping-i18n'),
                'flat_placeholder' => __('Enter flat number', 'wc-ukr-shipping-i18n'),
                'text_internal_error' => __('Something went wrong', 'wc-ukr-shipping-i18n'),
            ]
        ];

        $globals['i18n'] = array_replace_recursive(
            $globals['i18n'],
            apply_filters('wcus_checkout_i18n', $globals['i18n'], $translator->getCurrentLanguage())
        );
        $globals = $this->collectShippingMethods($globals);

        wp_localize_script('wcus_checkout_js', 'wc_ukr_shipping_globals', $globals);
    }

    private function getDefaultCities()
    {
        $locale = preg_replace(
            '/_.+$/',
            '',
            is_admin() ? get_user_locale() : $this->translateService->getCurrentLanguage()
        );

        if ($locale === 'uk') {
            $locale = 'ua';
        }

        return array_map(function($item) use($locale) {
            return [
                'name' => $item[$locale === 'ua' ? 'description' : 'description_ru'],
                'value' => $item['ref']
            ];
        }, WCUSHelper::getDefaultCities());
    }

    private function collectShippingMethods(array $globals): array
    {
        $ownShippingMethods = [
            WC_UKR_SHIPPING_NP_SHIPPING_NAME,
            WCUS_SHIPPING_METHOD_UKRPOSHTA,
            WCUS_SHIPPING_METHOD_NOVA_POST,
            WCUS_SHIPPING_METHOD_ROZETKA,
            WCUS_SHIPPING_METHOD_MEEST,
            WCUS_SHIPPING_METHOD_MEEST_ADDRESS,
        ];

        // Get active shipping methods for zones
        $zones = \WC_Shipping_Zones::get_zones();
        $activeShippingMethods = [];
        $shippingOptions = [];
        foreach ($zones as $zone) {
            /** @var \WC_Shipping_Method $method */
            foreach ($zone['shipping_methods'] ?? [] as $method) {
                if (in_array($method->id, $ownShippingMethods) && $method->is_enabled()) {
                    $activeShippingMethods[] = $method->id;
                    $shippingOptions[$method->get_rate_id()] = $this->getShippingOptions($method);
                }
            }
        }

        // Get active shipping methods for default zone
        $defaultZone = new \WC_Shipping_Zone(0);
        /** @var \WC_Shipping_Method $method */
        foreach ($defaultZone->get_shipping_methods() as $method) {
            if (in_array($method->id, $ownShippingMethods) && $method->is_enabled()) {
                $activeShippingMethods[] = $method->id;
                $shippingOptions[$method->get_rate_id()] = $this->getShippingOptions($method);
            }
        }

        $globals['shippingMethods'] = array_values(array_unique($activeShippingMethods));
        $globals['shippingOptions'] = $shippingOptions;

        return $globals;
    }

    private function getShippingOptions(\WC_Shipping_Method $method): array
    {
        if ($method->id === WCUS_SHIPPING_METHOD_NOVA_POSHTA) {
            return [
                'deliveryMethods' => $method->get_option('delivery_methods'),
                'combinePoshtomats' => $method->get_option('combine_poshtomats') === 'yes',
            ];
        }

        return [];
    }
}
