<?php

namespace kirillbdev\WCUkrShipping\Contracts;

use kirillbdev\WCUkrShipping\Model\OrderProduct;

if ( ! defined('ABSPATH')) {
    exit;
}

interface OrderDataInterface
{
    /**
     * @return float
     */
    public function getSubTotal();

    /**
     * @return float
     */
    public function getDiscountTotal();

    /**
     * @return float
     */
    public function getTotal();

    /**
     * @return float
     */
    public function getCalculatedTotal();

    /**
     * @return AddressInterface
     */
    public function getShippingAddress();

    /**
     * @return bool
     */
    public function isAddressShipping();

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @return bool
     */
    public function isShipToDifferentAddress();

    public function getShippingType(): ?string;

    /**
     * @return OrderProduct[]
     */
    public function getProducts();
}