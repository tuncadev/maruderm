<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Rates;

use kirillbdev\WCUkrShipping\Model\OrderProduct;

if ( ! defined('ABSPATH')) {
    exit;
}

final class RateShipmentDTO
{
    public string $carrierSlug;
    public string $shipToCountry;
    public float $declaredValue;
    public float $weight;
    public string $paymentMethod;
    public string $deliveryType;
    public bool $isFull;
    public ?array $dimensions;
    public ?string $shipToCarrierCityId;
    public ?string $shipToPUDOPointId;
    public ?string $serviceType;

    /**
     * @var OrderProduct[]
     */
    public array $products;
    public ?string $shipToCity;
    public ?string $shipToPostalCode;

    public function __construct(
        string $carrierSlug,
        string $shipToCountry,
        float $declaredValue,
        float $weight,
        string $paymentMethod,
        string $deliveryType,
        bool $isFull,
        ?array $dimensions = null,
        ?string $shipToCarrierCityId = null,
        ?string $shipToPUDOPointId = null,
        ?string $serviceType = null,
        array $products = [],
        ?string $shipToCity = null,
        ?string $shipToPostalCode = null
    ) {
        $this->carrierSlug = $carrierSlug;
        $this->shipToCountry = $shipToCountry;
        $this->declaredValue = $declaredValue;
        $this->weight = $weight;
        $this->paymentMethod = $paymentMethod;
        $this->deliveryType = $deliveryType;
        $this->isFull = $isFull;
        $this->dimensions = $dimensions;
        $this->shipToCarrierCityId = $shipToCarrierCityId;
        $this->shipToPUDOPointId = $shipToPUDOPointId;
        $this->serviceType = $serviceType;
        $this->products = $products;
        $this->shipToCity = $shipToCity;
        $this->shipToPostalCode = $shipToPostalCode;
    }
}
