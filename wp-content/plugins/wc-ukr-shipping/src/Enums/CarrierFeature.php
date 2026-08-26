<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Enums;

final class CarrierFeature
{
    /**
     * Selection of a warehouse, poshtomat or PUDO point on the checkout page.
     */
    public const PICKUP_POINTS = 'pickup_points';

    /**
     * Courier delivery to the customer address.
     */
    public const ADDRESS_DELIVERY = 'address_delivery';

    /**
     * Shipping cost estimation through the SmartyParcel Rates API.
     */
    public const RATE_CALCULATION = 'rate_calculation';

    /**
     * Creating a waybill and printing a label from the order.
     */
    public const SHIPPING_LABELS = 'shipping_labels';

    /**
     * Shipment status updates by the tracking number.
     */
    public const TRACKING = 'tracking';
}
