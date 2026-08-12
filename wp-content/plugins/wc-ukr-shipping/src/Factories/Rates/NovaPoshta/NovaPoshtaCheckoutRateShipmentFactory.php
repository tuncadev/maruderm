<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Factories\Rates\NovaPoshta;

use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Factories\Rates\CheckoutRateShipmentFactory;

class NovaPoshtaCheckoutRateShipmentFactory extends CheckoutRateShipmentFactory
{
    public function __construct(bool $useDimensions)
    {
        parent::__construct(CarrierSlug::NOVA_POSHTA, $useDimensions);
    }

    protected function getShipToCarrierCityId(): ?string
    {
        if ($this->get('shipping_type') === 'doors') {
            return $this->get("wcus_np_{$this->fieldGroup}_settlement_ref");
        }

        return $this->get("wcus_np_{$this->fieldGroup}_city");
    }

    protected function getDeliveryType(): string
    {
        if ($this->get("wcus_{$this->fieldGroup}_warehouse_type")) {
            return $this->get("wcus_{$this->fieldGroup}_warehouse_type") === 'warehouse' ? 'w2w' : 'w2l';
        }

        switch ($this->get('shipping_type')) {
            case 'doors':
                return 'w2d';
            case 'poshtomat':
                return 'w2l';
            default:
                return 'w2w';
        }
    }

    protected function isFull(): bool
    {
        return !empty($this->getShipToCarrierCityId());
    }
}
