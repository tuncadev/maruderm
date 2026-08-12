<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;

if ( ! defined('ABSPATH')) {
    exit;
}

class CreateLabelAction implements ActionInterface
{
    private SmartyParcelService $smartyParcelService;

    public function __construct()
    {
        $this->smartyParcelService = wcus_container()->make(SmartyParcelService::class);
    }

    public function execute(Context $context): void
    {
        $order = $context->getOrder();
        $shippingMethod = WCUSHelper::getOrderShippingMethod($order);
        $allowedMethods = [
            WCUS_SHIPPING_METHOD_NOVA_POSHTA,
            WCUS_SHIPPING_METHOD_UKRPOSHTA,
            WCUS_SHIPPING_METHOD_ROZETKA,
        ];
        if ($shippingMethod === null || !in_array($shippingMethod->get_method_id(), $allowedMethods)) {
            return;
        }
        if ($this->smartyParcelService->getLabelByOrderId($order->get_id()) !== null) {
            return;
        }

        // Schedule async action
        wp_schedule_single_event(time(), 'wcus_smartyparcel_auto_create_label', [$order->get_id()]);
    }
}
