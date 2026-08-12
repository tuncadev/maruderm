<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\SmartyParcel\Labels;

class CreateLabelResponseDto
{
    public int $id;
    public int $orderId;
    public string $labelId;
    public string $trackingNumber;
    public float $shipmentCost;
    public ?\DateTimeImmutable $estimatedDeliveryDate;
    public string $trackingStatus = '';

    public function __construct(
        int $id,
        int $orderId,
        string $labelId,
        string $trackingNumber,
        float $shipmentCost,
        ?\DateTimeImmutable $estimatedDeliveryDate = null,
        string $trackingStatus = ''
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->labelId = $labelId;
        $this->trackingNumber = $trackingNumber;
        $this->shipmentCost = $shipmentCost;
        $this->estimatedDeliveryDate = $estimatedDeliveryDate;
        $this->trackingStatus = $trackingStatus;
    }
}
