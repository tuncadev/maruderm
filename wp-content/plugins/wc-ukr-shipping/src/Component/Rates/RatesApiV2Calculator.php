<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Rates;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Contracts\Rates\RatesCalculatorInterface;
use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;

class RatesApiV2Calculator implements RatesCalculatorInterface
{
    private bool $includeCod;
    private SmartyParcelWPApi $api;

    public function __construct(
        bool $includeCod,
        SmartyParcelWPApi $api
    ) {
        $this->api = $api;
        $this->includeCod = $includeCod;
    }

    public function calculateRates(RateShipmentDTO $rateShipmentDTO): ?float
    {
        try {
            $shipTo = [
                'country_code' => $rateShipmentDTO->shipToCountry,
            ];
            if ($rateShipmentDTO->shipToPUDOPointId !== null) {
                $shipTo['pudo_point_id'] = $rateShipmentDTO->shipToPUDOPointId;
            } elseif (!empty($rateShipmentDTO->shipToCarrierCityId)) {
                $shipTo['carrier_city_id'] = $rateShipmentDTO->shipToCarrierCityId;
            } elseif ($rateShipmentDTO->shipToCity !== null) {
                $shipTo['city'] = $rateShipmentDTO->shipToCity;
            }
            if ($rateShipmentDTO->shipToPostalCode !== null) {
                $shipTo['postal_code'] = $rateShipmentDTO->shipToPostalCode;
            }

            $payload = [
                'carriers' => [
                    $rateShipmentDTO->carrierSlug,
                ],
                'billing' => [
                    'paid_by' => 'recipient',
                    'payment_method' => 'cash',
                ],
                'delivery_type' => $rateShipmentDTO->deliveryType,
                'ship_to' => $shipTo,
                'declared_value' => [
                    'amount' => $rateShipmentDTO->declaredValue,
                    'currency' => get_woocommerce_currency(),
                ],
                'weight' => [
                    'value' => $rateShipmentDTO->weight,
                    'unit' => 'kg'
                ],
            ];

            if ($rateShipmentDTO->serviceType !== null) {
                $payload['service_type'] = $rateShipmentDTO->serviceType;
            }

            if ($rateShipmentDTO->dimensions !== null) {
                $payload['dimensions'] = $rateShipmentDTO->dimensions;
                $payload['dimensions']['unit'] = 'cm';
            }

            if ((int)wc_ukr_shipping_get_option('wcus_rates_convert_currency') === 1) {
                $payload['convert_to_currency'] = get_woocommerce_currency();
            }
            $response = $this->api->sendRequest('/v1/rates/estimate', $payload);

            $cost = 0;
            $rates = $response['data'][0];
            foreach ($rates['detailed_charges'] ?? [] as $rate) {
                if ($rate['type'] === 'base') {
                    $cost += (float)$rate['amount'];
                } elseif ($rate['type'] === 'cod' && $this->includeCod) {
                    $cost += (float)$rate['amount'];
                }
            }

            return $cost === 0 ? null : $cost;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
