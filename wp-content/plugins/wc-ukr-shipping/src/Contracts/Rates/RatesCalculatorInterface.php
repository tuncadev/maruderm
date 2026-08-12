<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Contracts\Rates;

use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;

interface RatesCalculatorInterface
{
    public function calculateRates(RateShipmentDTO $rateShipmentDTO): ?float;
}
