<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Factories\Rates;

use kirillbdev\WCUkrShipping\Contracts\Rates\RatesCalculatorInterface;
use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;
use kirillbdev\WCUkrShipping\Foundation\AbstractShippingMethod;

interface RatesCalculatorFactoryInterface
{
    public function getRatesCalculator(RateShipmentDTO $rateShipmentDTO, AbstractShippingMethod $shippingMethod): RatesCalculatorInterface;
}
