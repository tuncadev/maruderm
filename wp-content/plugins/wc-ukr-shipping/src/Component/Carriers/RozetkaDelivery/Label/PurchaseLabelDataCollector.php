<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label;

use kirillbdev\WCUkrShipping\Component\SmartyParcel\AbstractShipmentCollector;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

/**
 * @deprecated
 */
class PurchaseLabelDataCollector extends AbstractShipmentCollector
{
    public function __construct(\WC_Order $order)
    {
        parent::__construct($order, CarrierSlug::ROZETKA_DELIVERY);
    }

    protected function collectShipFrom(): array
    {
        $sender = WCUSHelper::safeGetJsonOption('wcus_rozetka_ttn_sender');

        return [
            'name' => $sender['name'] ?? '',
            'phone' => $sender['phone'] ?? '',
            'email' => $sender['email'] ?? '',
            'pudo_point_id' => $sender['warehouse']['value'] ?? '',
            'city' => $sender['city']['name'] ?? '',
            'address_1' => $sender['warehouse']['name'] ?? '',
        ];
    }

    protected function collectShipToAAddress(): array
    {
        return [
            'pudo_point_id' => $this->orderShipping->get_meta('wcus_rozetka_warehouse_id'),
            'city' => $this->orderShipping->get_meta('wcus_rozetka_city_name'),
            'address_1' => $this->orderShipping->get_meta('wcus_rozetka_warehouse_name'),
        ];
    }
}
