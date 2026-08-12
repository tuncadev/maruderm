<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Rates;

use kirillbdev\WCUkrShipping\Contracts\Rates\RatesCalculatorInterface;
use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;

class FreeShippingRatesCalculator implements RatesCalculatorInterface
{
    private RatesCalculatorInterface $fallbackCalculator;
    private float $minAmount;

    public function __construct(RatesCalculatorInterface $fallbackCalculator, float $minAmount)
    {
        $this->fallbackCalculator = $fallbackCalculator;
        $this->minAmount = $minAmount;
    }

    public function calculateRates(RateShipmentDTO $rateShipmentDTO): ?float
    {
        if ($this->minAmount <= 0) {
            return null;
        }

        return $rateShipmentDTO->declaredValue >= $this->minAmount
            ? 0
            : $this->fallbackCalculator->calculateRates($rateShipmentDTO);
    }
}
