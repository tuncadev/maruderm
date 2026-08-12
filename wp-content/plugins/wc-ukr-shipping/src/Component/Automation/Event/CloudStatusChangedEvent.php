<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class CloudStatusChangedEvent implements EventInterface
{
    private string $newStatus;
    private ?string $newSubStatus;

    public function __construct(string $newStatus, ?string $newSubStatus)
    {
        $this->newStatus = $newStatus;
        $this->newSubStatus = $newSubStatus;
    }

    public function canProcess(Context $context): bool
    {
        return $context->getLabel()['cloud_status'] === $this->newStatus
            && $context->getLabel()['cloud_sub_status'] === $this->newSubStatus;
    }
}
