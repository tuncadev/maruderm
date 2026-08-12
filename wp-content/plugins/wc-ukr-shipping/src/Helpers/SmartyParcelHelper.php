<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Helpers;

use kirillbdev\WCUkrShipping\Enums\CarrierSlug;

final class SmartyParcelHelper
{
    public static function isConnected(): bool
    {
        return get_option(WCUS_OPTION_SMARTY_PARCEL_USER_STATUS) === 'connected';
    }

    public static function canPurchaseLabelForOrder(\WC_Order $order): bool
    {
        $forbiddenMethods = apply_filters('wcus_labels_forbidden_methods', [
            'local_pickup',
        ]);

        foreach ($forbiddenMethods as $method) {
            if ($order->has_shipping_method($method)) {
                return false;
            }
        }

        return true;
    }

    public static function getOrderCarrierSlug(\WC_order $order): ?string
    {
        $orderShipping = WCUSHelper::getOrderShippingMethod($order);
        if ($orderShipping === null) {
            return null;
        }

        return self::getCarrierFromShippingMethod($orderShipping->get_method_id());
    }

    public static function getCarrierFromShippingMethod(string $shippingMethodId): ?string
    {
        switch ($shippingMethodId) {
            case WCUS_SHIPPING_METHOD_NOVA_POSHTA:
                return CarrierSlug::NOVA_POSHTA;
            case WCUS_SHIPPING_METHOD_UKRPOSHTA:
            case WCUS_SHIPPING_METHOD_UKRPOSHTA_ADDRESS:
                return CarrierSlug::UKRPOSHTA;
            case WCUS_SHIPPING_METHOD_ROZETKA:
                return CarrierSlug::ROZETKA_DELIVERY;
            case WCUS_SHIPPING_METHOD_NOVA_POST:
                return CarrierSlug::NOVA_POST;
            case WCUS_SHIPPING_METHOD_NOVA_GLOBAL_ADDRESS:
                return CarrierSlug::NOVA_GLOBAL;
            case WCUS_SHIPPING_METHOD_MEEST:
            case WCUS_SHIPPING_METHOD_MEEST_ADDRESS:
                    return CarrierSlug::MEEST;
        }

        return null;
    }

    public static function getOrderStateName(string $countryCode, string $stateCode): string
    {
        $states = WC()->countries->get_states($countryCode);
        if (empty($states) || !is_array($states)) {
            return $stateCode;
        }

        return $states[$stateCode] ?? $stateCode;
    }
}
