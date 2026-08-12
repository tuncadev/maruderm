<?php

namespace kirillbdev\WCUkrShipping\Foundation;

use kirillbdev\WCUkrShipping\Component\Carriers\Meest\Rates\MeestCheckoutRateShipmentFactory;
use kirillbdev\WCUkrShipping\Factories\Rates\CheckoutRateShipmentFactory;

if ( ! defined('ABSPATH')) {
    exit;
}

class MeestAddressShipping extends AbstractShippingMethod
{
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = WCUS_SHIPPING_METHOD_MEEST_ADDRESS;
        $this->method_title = __('Meest Post Address', 'wc-ukr-shipping-i18n');
        $this->method_description = 'Meest Post Address by WC Ukraine Shipping';

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        $this->init();
    }

    public function __get($name)
    {
        return $this->$name;
    }

    function init(): void
    {
        $this->init_settings();
        $this->init_form_fields();

        $this->title = $this->get_option('title');

        // @phpstan-ignore-next-line
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Name', 'woocommerce' ),
                'type' => 'text',
                'description' => '',
                'default' => __('Meest Post Address', 'wc-ukr-shipping-i18n'),
            ],
            'cost_calculation_type' => [
                'title' => __('Type of shipping cost calculation', 'wc-ukr-shipping-i18n'),
                'type' => 'select',
                'description' => __('Access to the SmartyParcel Rates API is only available on paid plans', 'wc-ukr-shipping-i18n'),
                'desc_tip' => true,
                'class' => 'j-wcus-shipping-calc-type',
                'options' => [
                    'fixed' => __('Fixed', 'wc-ukr-shipping-i18n'),
                    'rates_api'  => __('SmartyParcel Rates API', 'wc-ukr-shipping-i18n'),
                ],
                'default' => 'fixed',
            ],
            'fixed_cost' => [
                'title' => __('Fixed shipping cost', 'wc-ukr-shipping-i18n'),
                'type' => 'text',
                'default' =>  100,
                'desc_tip' => true,
                'class' => 'j-wcus-shipping-fixed-cost',
                'sanitize_callback' => [$this, 'sanitizePrice'],
            ],
            'include_cod' => [
                'label' => __('Include COD when calculating shipping cost', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked and function supported by carrier, the COD service will be included in shipping cost.', 'wc-ukr-shipping-i18n'),
                'default' =>  'yes',
                'desc_tip' => true,
                'class' => 'j-wcus-shipping-option-cod',
            ],
            'add_cost_to_order' => [
                'label' => __('Include shipping cost to order total', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked, the shipping cost will be added to the order total.', 'wc-ukr-shipping-i18n'),
                'default' =>  'yes',
                'desc_tip' => true,
            ],
            'enable_free_shipping' => [
                'label' => __('Enable free shipping rule', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked, free shipping would be available based on order amount.', 'wc-ukr-shipping-i18n'),
                'default' => 'no',
                'desc_tip' => true,
            ],
            'free_shipping_min_amount' => [
                'title' => __('Minimum amount for free shipping', 'wc-ukr-shipping-i18n'),
                'type' => 'text',
                'default' => 1000,
                'desc_tip' => true,
                'sanitize_callback' => [$this, 'sanitizePrice'],
            ],
            'free_shipping_title' => [
                'title' => __('Free shipping method name', 'wc-ukr-shipping-i18n'),
                'type' => 'text',
                'default' => __('Free shipping', 'wc-ukr-shipping-i18n'),
            ],
        ];
    }

    protected function getCheckoutRateShipmentFactory(): CheckoutRateShipmentFactory
    {
        return new MeestCheckoutRateShipmentFactory('door');
    }
}
