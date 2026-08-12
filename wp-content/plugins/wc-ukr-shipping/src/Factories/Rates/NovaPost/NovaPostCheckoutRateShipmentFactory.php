<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Factories\Rates\NovaPost;

use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Factories\Rates\CheckoutRateShipmentFactory;

class NovaPostCheckoutRateShipmentFactory extends CheckoutRateShipmentFactory
{
    public function __construct()
    {
        parent::__construct(CarrierSlug::NOVA_POST);
    }

    protected function getShipToPUDOPointId(): ?string
    {
        return $this->get("wcus_nova_post_{$this->fieldGroup}_warehouse");
    }

    protected function isFull(): bool
    {
        return !empty($this->getShipToPUDOPointId());
    }
}
