<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Meest\Rates;

use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Factories\Rates\CheckoutRateShipmentFactory;

class MeestCheckoutRateShipmentFactory extends CheckoutRateShipmentFactory
{
    private string $deliveryType;

    public function __construct(string $deliveryType)
    {
        parent::__construct(CarrierSlug::MEEST);
        $this->deliveryType = $deliveryType;
    }

    protected function getShipToPUDOPointId(): ?string
    {
        return $this->deliveryType === 'warehouse'
            ? $this->get("wcus_meest_{$this->fieldGroup}_warehouse")
            : null;
    }

    protected function getShipToCity(): ?string
    {
        return $this->deliveryType === 'door'
            ? $this->get('ship_to')['city'] ?? null
            : null;
    }

    protected function isFull(): bool
    {
        if ($this->deliveryType === 'door') {
            return !empty($this->getShipToCity());
        }
        return !empty($this->getShipToPUDOPointId());
    }
}
