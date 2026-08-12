<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta;

final class ShippingOptionDefaultMapper
{
    public function getShippingCalculationTypeDefault(): string
    {
        return wc_ukr_shipping_get_option('wc_ukr_shipping_np_price_type') === 'calculate'
            ? 'rates_api'
            : 'fixed';
    }

    public function getFreeShippingConditionDefault(): string
    {
        return wc_ukr_shipping_get_option('wc_ukr_shipping_np_price_type') === 'relative_to_total'
            ? 'yes'
            : 'no';
    }

    public function getFreeShippingMinAmountDefault(): ?float
    {
        $rules = wc_ukr_shipping_get_option('wc_ukr_shipping_np_relative_price');
        if (!$rules) {
            return null;
        }

        $rules = json_decode($rules, true);
        if (json_last_error()) {
            return null;
        }

        $max = 0;
        foreach ($rules as $rule) {
            if ((float)$rule['total'] > $max) {
                $max = (float)$rule['total'];
            }
        }

        return $max;
    }

    public function getDeliveryMethodsDefault(): array
    {
        $default = [
            'warehouse'
        ];
        if ((int)wc_ukr_shipping_get_option('wc_ukr_shipping_address_shipping') === 1) {
            $default[] = 'doors';
        }
        if ((int)wc_ukr_shipping_get_option('wcus_show_poshtomats') === 1) {
            $default[] = 'poshtomat';
        }

        return $default;
    }
}
