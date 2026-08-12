<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class SendEmailAction implements ActionInterface
{
    private string $subject;
    private string $message;
    private string $sendTo;

    public function __construct(string $subject, string $message, string $sendTo)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->sendTo = $sendTo;
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
                $context->getLabel()['tracking_number'],
                $context->getLabel()['carrier_status'],
                $order->getOrigin()->get_id(),
                $order->getOrigin()->get_billing_first_name(),
                $order->getOrigin()->get_billing_last_name(),
                $order->getCity(),
                $order->getAddress1(),
                $context->getLabel()['metadata']['estimated_delivery_date'] ?? '{{carrier_edd}}',
            ],
            $this->message
        );

        if ($this->sendTo === 'customer') {
            wc_mail($order->getOrigin()->get_billing_email(), $this->subject, $compiled);
        } elseif ($this->sendTo === 'admin') {
            $adminEmail = get_option('admin_email');
            if ($adminEmail) {
                wc_mail($adminEmail, $this->subject, $compiled);
            }
        }
    }
}
