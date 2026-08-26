<?php

namespace kirillbdev\WCUkrShipping\DB;

use kirillbdev\WCUkrShipping\Enums\CarrierSlug;

if ( ! defined('ABSPATH')) {
    exit;
}

class OptionsRepository
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public static function getOption($key)
    {
        $defaults = [
            'wc_ukr_shipping_np_method_title' => 'Нова Пошта',
            'wc_ukr_shipping_np_block_pos' => 'billing',
            'wc_ukr_shipping_np_save_warehouse' => 1,
            'wc_ukr_shipping_np_price_type' => 'fixed',
            'wc_ukr_shipping_np_price' => 0,
            'wc_ukr_shipping_np_cargo_type' => 'Cargo',
            'wcus_inject_additional_fields' => 0,
            'wcus_cost_view_only' => 0,
            'wcus_combine_poshtomats' => 0,

            // Global
            'wcus_active_carriers' => [
                CarrierSlug::NOVA_POSHTA,
                CarrierSlug::UKRPOSHTA,
                CarrierSlug::ROZETKA_DELIVERY,
                CarrierSlug::NOVA_POST,
                CarrierSlug::NOVA_GLOBAL,
            ],
            'wcus_rates_convert_currency' => 0,

            // Nova Poshta
            'wc_ukr_shipping_address_shipping' => 1,
            'wcus_show_poshtomats' => 1,
            'wcus_np_use_online_directory' => 0,
            'wcus_ttn_global_params_default' => 0,

            // Ukrposhta
            'wcus_ukrposhta_service_type' => 'ukrposhta_standard',
            'wcus_ukrposhta_cost_view_only' => 0,
            'wcus_ukrposhta_cod_payment_active' => 0,
            'wcus_ukrposhta_price_type' => 'fixed',
            'wcus_ukrposhta_price' => 0,

            // Nova Post
            'wcus_nova_post_cost_view_only' => 0,
            'wcus_nova_post_fixed_cost' => 0,

            // Rozetka delivery
            'wcus_rozetka_fixed_cost' => 0,
            'wcus_rozetka_cost_view_only' => 0,

            // SmartyParcel
            'wcus_use_smartyparcel_locator' => 0,
        ];

        return get_option($key, isset($defaults[$key]) ? $defaults[$key] : null);
    }

    public function save($data)
    {
        foreach ($data['wc_ukr_shipping'] as $key => $value) {
            if (is_array($value)) {
                update_option('wc_ukr_shipping_' . $key, json_encode($value));
            } else {
                update_option('wc_ukr_shipping_' . $key, sanitize_text_field($value));
            }
        }

        foreach ($data['wcus'] as $key => $value) {
            if (is_array($value)) {
                update_option('wcus_' . $key, json_encode($value));
            } else {
                update_option('wcus_' . $key, sanitize_text_field($value));
            }
        }

        // Flush WooCommerce Shipping Cache
        delete_option('_transient_shipping-transient-version');
    }

    public function deleteAll()
    {
        delete_option('_transient_shipping-transient-version');
    }
}