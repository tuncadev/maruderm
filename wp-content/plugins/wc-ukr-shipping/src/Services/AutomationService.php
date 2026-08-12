<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Component\Automation\RuleExecutor;
use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class AutomationService
{
    public const EVENT_LABEL_CREATED = 'label_created';
    public const EVENT_LABEL_ATTACHED = 'label_attached';
    public const EVENT_LABEL_VOIDED = 'label_voided';
    public const EVENT_SP_TRACKING_STATUS_CHANGED = 'sp_tracking_status_changed';
    public const EVENT_SP_CARRIER_STATUS_CHANGED = 'sp_carrier_status_changed';
    public const EVENT_ORDER_STATUS_CHANGED = 'order_status_changed';

    private AutomationRulesRepository $rulesRepository;
    private RuleExecutor $ruleExecutor;

    public function __construct(
        AutomationRulesRepository $rulesRepository,
        RuleExecutor $ruleExecutor
    ) {
        $this->rulesRepository = $rulesRepository;
        $this->ruleExecutor = $ruleExecutor;
    }

    public function executeEvent(string $event, Context $context): void
    {
        $rules = $this->rulesRepository->findActiveByEvent($event);
        if (count($rules) === 0) {
            return;
        }

        foreach ($rules as $rule) {
            $this->ruleExecutor->tryExecute($rule, $context);
        }
    }
}
