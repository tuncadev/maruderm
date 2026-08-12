<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Services\AutomationService;

if ( ! defined('ABSPATH')) {
    exit;
}

class EventFactory
{
    public function createFromRule(array $rule): EventInterface
    {
        switch ($rule['event_name']) {
            case AutomationService::EVENT_SP_TRACKING_STATUS_CHANGED:
                return new CloudStatusChangedEvent(
                    $rule['event_data']['newStatus'],
                    $rule['event_data']['newSubStatus'] ?? null
                );
            case AutomationService::EVENT_SP_CARRIER_STATUS_CHANGED:
                return new CarrierStatusChangedEvent(
                    $rule['event_data']['carrierSlug'] ?? 'nova_poshta',
                    $rule['event_data']['newStatus']
                );
            case AutomationService::EVENT_ORDER_STATUS_CHANGED:
                return new OrderStatusChangedEvent(
                    $rule['event_data']['newStatus'],
                    $rule['event_data']['adminOnly'] ?? true
                );
            case AutomationService::EVENT_LABEL_CREATED:
            case AutomationService::EVENT_LABEL_ATTACHED:
            case AutomationService::EVENT_LABEL_VOIDED:
                return new SimpleEvent();
            default:
                throw new \LogicException("Invalid event '{$rule['event_name']}'");
        }
    }
}
