<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label;

use kirillbdev\WCUkrShipping\Component\SmartyParcel\LabelRequestBuilderInterface;

class BatchLabelRequestAdapter implements LabelRequestBuilderInterface
{
    private PurchaseLabelDataCollector $collector;

    public function __construct(PurchaseLabelDataCollector $collector)
    {
        $this->collector = $collector;
    }

    public function build(): array
    {
        $data = $this->collector->collect();

        return [
            'carrier_slug' => $data['carrier_slug'],
            'billing' => $data['billing'],
            'shipment' => $data['shipment'],
            'service_options' => $data['service_options'] ?? [],
        ];
    }
}
