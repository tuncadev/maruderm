<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Component\Validation\CheckoutValidatorInterface;
use kirillbdev\WCUkrShipping\Component\Validation\MeestCheckoutValidator;
use kirillbdev\WCUkrShipping\Component\Validation\NovaPoshtaCheckoutValidator;
use kirillbdev\WCUkrShipping\Component\Validation\NovaPostCheckoutValidator;
use kirillbdev\WCUkrShipping\Component\Validation\RozetkaDeliveryCheckoutValidator;
use kirillbdev\WCUkrShipping\Component\Validation\UkrposhtaCheckoutValidator;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutValidator implements ModuleInterface
{
    public function init(): void
    {
        add_action('woocommerce_checkout_process', [$this, 'validateFields']);
        add_filter('woocommerce_checkout_fields', [$this, 'removeDefaultFieldsFromValidation'], 99);
    }

    public function removeDefaultFieldsFromValidation(array $fields): array
    {
        if ( ! wp_doing_ajax() || empty($_POST)) {
            return $fields;
        }

        if ($this->isPluginShippingMethodSelected()) {
            foreach (['billing', 'shipping'] as $type) {
                unset($fields[$type][$type . '_address_1']);
                unset($fields[$type][$type . '_address_2']);
                unset($fields[$type][$type . '_city']);
                unset($fields[$type][$type . '_state']);
                unset($fields[$type][$type . '_postcode']);
            }

            $paymentMethod = $_POST['payment_method'] ?? '';
            if (WCUSHelper::hasChosenShippingMethod('wcus_ukrposhta_shipping') && $paymentMethod === 'cod') {
                $altType = $this->getTypeToValidate($_POST) === 'billing' ? 'shipping' : 'billing';
                unset($fields[$altType]["wcus_{$altType}_middlename"]);
            } else {
                unset($fields['billing']["wcus_billing_middlename"]);
                unset($fields['shipping']["wcus_shipping_middlename"]);
            }
        } else {
            unset($fields['billing']["wcus_billing_middlename"]);
            unset($fields['shipping']["wcus_shipping_middlename"]);
            unset($fields['shipping']['wcus_shipping_phone']);
        }

        return $fields;
    }

    public function validateFields(): void
    {
        if ($this->isPluginShippingMethodSelected()) {
           $validator = $this->getCheckoutValidator();
           if ($validator !== null) {
               $validator->validate($_POST);
           }
        }
    }

    private function isPluginShippingMethodSelected(): bool
    {
        $pluginShippingMethods = [
            WC_UKR_SHIPPING_NP_SHIPPING_NAME,
            WCUS_SHIPPING_METHOD_UKRPOSHTA,
            WCUS_SHIPPING_METHOD_NOVA_POST,
            WCUS_SHIPPING_METHOD_ROZETKA,
            WCUS_SHIPPING_METHOD_MEEST,
        ];

        foreach ($pluginShippingMethods as $method) {
            if (WCUSHelper::hasChosenShippingMethod($method)) {
                return true;
            }
        }

        return false;
    }

    private function getCheckoutValidator(): ?CheckoutValidatorInterface
    {
        if (WCUSHelper::hasChosenShippingMethod(WC_UKR_SHIPPING_NP_SHIPPING_NAME)) {
            return new NovaPoshtaCheckoutValidator();
        } elseif (WCUSHelper::hasChosenShippingMethod(WCUS_SHIPPING_METHOD_UKRPOSHTA)) {
            return new UkrposhtaCheckoutValidator();
        } elseif (WCUSHelper::hasChosenShippingMethod(WCUS_SHIPPING_METHOD_NOVA_POST)) {
            return new NovaPostCheckoutValidator();
        } elseif (WCUSHelper::hasChosenShippingMethod(WCUS_SHIPPING_METHOD_ROZETKA)) {
            return new RozetkaDeliveryCheckoutValidator();
        } elseif (WCUSHelper::hasChosenShippingMethod(WCUS_SHIPPING_METHOD_MEEST)) {
            return new MeestCheckoutValidator();
        }

        return null;
    }

    private function getTypeToValidate(array $data): string
    {
        if (isset($data['ship_to_different_address']) && 1 === (int)$data['ship_to_different_address']) {
            return 'shipping';
        }

        return 'billing';
    }
}
