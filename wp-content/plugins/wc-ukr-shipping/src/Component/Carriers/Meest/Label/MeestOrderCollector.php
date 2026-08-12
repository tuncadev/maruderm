<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Meest\Label;

use kirillbdev\WCUkrShipping\Component\SmartyParcel\BaseOrderCollector;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;

class MeestOrderCollector extends BaseOrderCollector
{
    public function __construct(\WC_Order $order)
    {
        parent::__construct($order, CarrierSlug::MEEST);
    }

    protected function collectShipTo(): array
    {
        $data = parent::collectShipTo();

        if ($this->orderShipping->get_method_id() === WCUS_SHIPPING_METHOD_MEEST) {
            $data['pudoPointId'] = $this->orderShipping->get_meta('wcus_pudo_point_id');
            $data['address1'] = $this->orderShipping->get_meta('wcus_pudo_point_name');
            $data['city'] = $this->orderShipping->get_meta('wcus_pudo_city_name');
            unset($data['state']);
            unset($data['district']);
            unset($data['address2']);
            unset($data['postalCode']);
        }

        return $data;
    }
}
