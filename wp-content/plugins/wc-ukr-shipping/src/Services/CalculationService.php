<?php

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;
use kirillbdev\WCUkrShipping\Factories\Rates\RatesCalculatorFactory;
use kirillbdev\WCUkrShipping\Factories\Rates\RatesCalculatorFactoryInterface;
use kirillbdev\WCUkrShipping\Foundation\AbstractShippingMethod;

if ( ! defined('ABSPATH')) {
    exit;
}

class CalculationService
{
    private SmartyParcelWPApi $api;
    private array $ratesCache = [];

    public function __construct()
    {
        $this->api = wcus_container()->make(SmartyParcelWPApi::class);
    }

    public function calculateRates(RateShipmentDTO $rateShipmentDTO, AbstractShippingMethod $shippingMethod): ?float
    {
        // Checking if exist in cache
        // todo: provide cache interface instead
        if (array_key_exists($rateShipmentDTO->carrierSlug, $this->ratesCache)) {
            return $this->ratesCache[$rateShipmentDTO->carrierSlug];
        }

        // Check is shipment ready for calculation
        if (!$rateShipmentDTO->isFull) {
            return null;
        }
        $factory = $this->getRatesCalculatorFactory($rateShipmentDTO);

        // todo: apply_filters('wcus_calculate_shipping_cost', $cost, $orderData)

        $cost = $factory->getRatesCalculator($rateShipmentDTO, $shippingMethod)
            ->calculateRates($rateShipmentDTO);

        $this->ratesCache[$rateShipmentDTO->carrierSlug] = $cost;

        return $cost;
    }

    private function getRatesCalculatorFactory(RateShipmentDTO $rateShipmentDTO): RatesCalculatorFactoryInterface
    {
        return new RatesCalculatorFactory($this->api);
    }
}
