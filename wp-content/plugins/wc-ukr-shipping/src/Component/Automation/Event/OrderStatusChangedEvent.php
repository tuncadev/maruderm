<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrderStatusChangedEvent implements EventInterface
{
    private string $newStatus;
    private bool $adminOnly;

    public function __construct(string $newStatus, bool $adminOnly)
    {
        $this->newStatus = $newStatus;
        $this->adminOnly = $adminOnly;
    }

    public function canProcess(Context $context): bool
    {
        $this->newStatus = WCUSHelper::removeOrderStatusPrefix($this->newStatus);

        $shouldCheck = true;
        if ($this->adminOnly && !is_admin()) {
            $shouldCheck = false;
        }

        return $shouldCheck && $context->getOrder()->has_status($this->newStatus);
    }
}
