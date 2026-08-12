<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class AddOrderNoteAction implements ActionInterface
{
    private string $message;
    private string $type;

    public function __construct(string $message, string $type)
    {
        $this->message = $message;
        $this->type = $type;
    }

    public function execute(Context $context): void
    {
        $order = wcus_wrap_order($context->getOrder());
        $compiled = str_replace(
            [
                '{{tracking_number}}',
                '{{carrier_status}}',
                '{{order_id}}',
                '{{billing_firstname}}',
                '{{billing_lastname}}',
                '{{city}}',
                '{{address}}',
                '{{carrier_edd}}',
            ],
            [
                $context->getLabel()['tracking_number'] ?? '',
                $context->getLabel()['carrier_status'] ?? '',
                $order->getOrigin()->get_id(),
                $order->getOrigin()->get_billing_first_name(),
                $order->getOrigin()->get_billing_last_name(),
                $order->getCity(),
                $order->getAddress1(),
                $context->getLabel()['metadata']['estimated_delivery_date'] ?? '{{carrier_edd}}',
            ],
            $this->message
        );
        $order->getOrigin()->add_order_note($compiled, $this->type === 'customer');
    }
}
