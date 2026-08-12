<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class CarrierStatusChangedEvent implements EventInterface
{
    private string $carrierSlug;

    /**
     * @var string[]
     */
    private array $newStatus;

    public function __construct(string $carrierSlug, array $newStatus)
    {
        $this->carrierSlug = $carrierSlug;
        $this->newStatus = $newStatus;
    }

    public function canProcess(Context $context): bool
    {
        return $this->carrierSlug === $context->getLabel()['carrier_slug']
            && in_array($context->getLabel()['carrier_status_code'], $this->newStatus);
    }
}
