<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label;

use kirillbdev\WCUkrShipping\Component\SmartyParcel\BaseOrderCollector;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;

class RozetkaOrderCollector extends BaseOrderCollector
{
    public function __construct(\WC_Order $order)
    {
        parent::__construct($order, CarrierSlug::ROZETKA_DELIVERY);
    }

    protected function collectShipTo(): array
    {
        $data = parent::collectShipTo();
        $data['pudoPointId'] = $this->orderShipping->get_meta('wcus_rozetka_warehouse_id');
        $data['address1'] = $this->orderShipping->get_meta('wcus_rozetka_warehouse_name');
        $data['city'] = $this->orderShipping->get_meta('wcus_rozetka_city_name');
        unset($data['state']);
        unset($data['district']);
        unset($data['address2']);
        unset($data['postalCode']);

        return $data;
    }

    protected function collectDefaults(): array
    {
        return array_merge(parent::collectDefaults(), [
            'paidBy' => wc_ukr_shipping_get_option('wcus_rozetka_ttn_default_payer'),
        ]);
    }
}
