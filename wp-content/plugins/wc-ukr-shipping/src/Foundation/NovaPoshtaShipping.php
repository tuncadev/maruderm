<?php

namespace kirillbdev\WCUkrShipping\Foundation;

use kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\ShippingOptionDefaultMapper;
use kirillbdev\WCUkrShipping\Factories\Rates\CheckoutRateShipmentFactory;
use kirillbdev\WCUkrShipping\Factories\Rates\NovaPoshta\NovaPoshtaCheckoutRateShipmentFactory;

if ( ! defined('ABSPATH')) {
    exit;
}

class NovaPoshtaShipping extends AbstractShippingMethod
{
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = WCUS_SHIPPING_METHOD_NOVA_POSHTA;
        $this->method_title = WC_UKR_SHIPPING_NP_SHIPPING_TITLE;
        $this->method_description = 'Nova Poshta by WC Ukraine Shipping';

        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        $this->init_settings();
        $this->init_form_fields();

        $this->title = $this->get_option('title');

        // @phpstan-ignore-next-line
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields(): void
    {
        $optionMapper = new ShippingOptionDefaultMapper();
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Name', 'woocommerce' ),
                'type' => 'text',
                'description' => '',
                'default' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_method_title'),
            ],
            'delivery_methods' => [
                'title' => __('Delivery types', 'wc-ukr-shipping-i18n'),
                'type' => 'multiselect',
                'options' => [
                    'warehouse' => __('To warehouse', 'wc-ukr-shipping-i18n'),
                    'doors'  => __('By courier', 'wc-ukr-shipping-i18n'),
                    'poshtomat'  => __('To poshtomat', 'wc-ukr-shipping-i18n'),
                ],
                'default' => $optionMapper->getDeliveryMethodsDefault(),
                'desc_tip' => true,
                'description' => __('You can choose which types of delivery will be processed by this shipping method or create several shipping methods with concrete delivery types separately.', 'wc-ukr-shipping-i18n'),
                'sanitize_callback' => [$this, 'sanitizeDeliveryMethods'],
            ],
            'combine_poshtomats' => [
                'label' => __('Combine poshtomats and warehouses', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'default' =>  (int)wc_ukr_shipping_get_option('wcus_combine_poshtomats') === 1 ? 'yes' : 'no',
                'desc_tip' => true,
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
                'default' => $optionMapper->getShippingCalculationTypeDefault(),
            ],
            'fixed_cost' => [
                'title' => __('Fixed shipping cost', 'wc-ukr-shipping-i18n'),
                'type' => 'text',
                'default' =>  (float)wc_ukr_shipping_get_option('wc_ukr_shipping_np_price'),
                'desc_tip' => true,
                'class' => 'j-wcus-shipping-fixed-cost',
                'sanitize_callback' => [$this, 'sanitizePrice'],
            ],
            'include_cod' => [
                'label' => __('Include COD when calculating shipping cost', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked and function supported by carrier, the COD service will be included in shipping cost.', 'wc-ukr-shipping-i18n'),
                'default' =>  (int)wc_ukr_shipping_get_option('wcus_cod_payment_active') === 1 ? 'yes' : 'no',
                'desc_tip' => true,
                'class' => 'j-wcus-shipping-option-cod',
            ],
            'send_dimensions' => [
                'label' => __('Send dimensions when calculating shipping cost', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked and function supported by carrier, the product dimensions will be send to rates api.', 'wc-ukr-shipping-i18n'),
                'default' =>  'no',
                'desc_tip' => true,
                'class' => 'j-wcus-shipping-option-cod',
            ],
            'add_cost_to_order' => [
                'label' => __('Include shipping cost to order total', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked, the shipping cost will be added to the order total.', 'wc-ukr-shipping-i18n'),
                'default' =>  (int)wc_ukr_shipping_get_option('wcus_cost_view_only') === 1 ? 'no' : 'yes',
                'desc_tip' => true,
            ],
            'enable_free_shipping' => [
                'label' => __('Enable free shipping rule', 'wc-ukr-shipping-i18n'),
                'type' => 'checkbox',
                'description' => __('If checked, free shipping would be available based on order amount.', 'wc-ukr-shipping-i18n'),
                'default' => $optionMapper->getFreeShippingConditionDefault(),
                'desc_tip' => true,
            ],
            'free_shipping_min_amount' => [
                'title' => __('Minimum amount for free shipping', 'wc-ukr-shipping-i18n'),
                'type' => 'text',
                'default' => $optionMapper->getFreeShippingMinAmountDefault(),
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

    /**
     * @param mixed $value
     * @return mixed
     */
    public function sanitizeDeliveryMethods($value)
    {
        if (!is_array($value) || count($value) < 1) {
            throw new \InvalidArgumentException('You should select at least one delivery method');
        }

        return $value;
    }

    protected function getCheckoutRateShipmentFactory(): CheckoutRateShipmentFactory
    {
        return new NovaPoshtaCheckoutRateShipmentFactory($this->get_option('send_dimensions') === 'yes');
    }
}
