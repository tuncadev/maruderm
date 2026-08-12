<?php

namespace kirillbdev\WCUkrShipping\Model;

if ( ! defined('ABSPATH')) {
    exit;
}

class WCUSOrder
{
    /**
     * @var \WC_Order
     */
    private $wcOrder;

    /**
     * @param \WC_Order $wcOrder
     */
    public function __construct($wcOrder)
    {
        $this->wcOrder = $wcOrder;
    }

    public function getOrigin(): \WC_Order
    {
        return $this->wcOrder;
    }

    public function getCity(): string
    {
        return get_option('woocommerce_ship_to_destination') === 'billing_only'
            ? $this->wcOrder->get_billing_city()
            : $this->wcOrder->get_shipping_city();
    }

    public function getAddress1(): string
    {
        return get_option('woocommerce_ship_to_destination') === 'billing_only'
            ? $this->wcOrder->get_billing_address_1()
            : $this->wcOrder->get_shipping_address_1();
    }
}
