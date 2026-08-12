<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Factories\Rates;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Component\Rates\FixedRatesCalculator;
use kirillbdev\WCUkrShipping\Component\Rates\FreeShippingRatesCalculator;
use kirillbdev\WCUkrShipping\Component\Rates\RatesApiV2Calculator;
use kirillbdev\WCUkrShipping\Contracts\Rates\RatesCalculatorInterface;
use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;
use kirillbdev\WCUkrShipping\Foundation\AbstractShippingMethod;

class RatesCalculatorFactory implements RatesCalculatorFactoryInterface
{
    private SmartyParcelWPApi $api;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
    }

    public function getRatesCalculator(RateShipmentDTO $rateShipmentDTO, AbstractShippingMethod $shippingMethod): RatesCalculatorInterface
    {
        $calculator = $this->getRealRatesCalculator($rateShipmentDTO, $shippingMethod);
        if ($shippingMethod->get_option('enable_free_shipping') === 'yes') {
            return new FreeShippingRatesCalculator(
                $calculator,
                (float)$shippingMethod->get_option('free_shipping_min_amount')
            );
        } else {
            return $calculator;
        }
    }

    private function getRealRatesCalculator(RateShipmentDTO $dto, AbstractShippingMethod $shippingMethod): RatesCalculatorInterface
    {
        $calcType = $shippingMethod->get_option('cost_calculation_type');
        switch ($calcType) {
            case 'fixed':
                return new FixedRatesCalculator(
                    (float)$shippingMethod->get_option('fixed_cost')
                );
            case 'rates_api':
                return new RatesApiV2Calculator(
                    $shippingMethod->get_option('include_cod') === 'yes'
                        && wc_ukr_shipping_get_option('wcus_cod_payment_id') === $dto->paymentMethod,
                    $this->api
                );
            default:
                return new FixedRatesCalculator(0);
        }
    }
}
