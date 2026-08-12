<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Validation;

interface CheckoutValidatorInterface
{
    public function validate(array $data): void;
}
