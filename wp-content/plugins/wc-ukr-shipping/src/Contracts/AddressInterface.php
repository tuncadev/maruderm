<?php

namespace kirillbdev\WCUkrShipping\Contracts;

if ( ! defined('ABSPATH')) {
    exit;
}

interface AddressInterface
{
    /**
     * @return string
     */
    public function getAreaRef();

    /**
     * @return string
     */
    public function getCityRef();

    public function getCityName(): ?string;

    /**
     * @return string
     */
    public function getWarehouseRef();

    public function getWarehouseName(): ?string;

    /**
     * @return string
     */
    public function getCustomAddress();

    /**
     * @return bool
     */
    public function isAddressShipping();

    public function getSettlementInfo(string $key): string;

    public function getStreetInfo(string $key): string;

    public function getHouse(): string;

    public function getFlat(): string;
}
