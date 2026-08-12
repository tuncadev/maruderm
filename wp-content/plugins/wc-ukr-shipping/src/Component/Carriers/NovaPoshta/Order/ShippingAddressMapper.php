<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\Order;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

class ShippingAddressMapper
{
    public function getShippingAddress(\WC_Order $order): array
    {
        $orderShipping = WCUSHelper::getOrderShippingMethod($order);
        if ($orderShipping->get_meta('wcus_warehouse_ref')) {
            return [
                'type' => 'pudo',
                'pudo_point_id' => $orderShipping->get_meta('wcus_warehouse_ref'),
                'city' => $orderShipping->get_meta('wcus_city_name'),
                'address_1' => $orderShipping->get_meta('wcus_warehouse_name'),
                'extra' => [
                    'pudo_city_id' => $orderShipping->get_meta('wcus_city_ref'),
                ],
            ];
        }

        return [
            'type' => 'home',
            'country_code' => 'UA',
            'city' => $orderShipping->get_meta('wcus_settlement_name'),
            'state' => $orderShipping->get_meta('wcus_settlement_area'),
            'district' => $orderShipping->get_meta('wcus_settlement_region'),
            'address_1' => $orderShipping->get_meta('wcus_street_name'),
            'address_2' => $orderShipping->get_meta('wcus_house'),
            'address_3' => $orderShipping->get_meta('wcus_flat'),
            'extra' => [
                'np_settlement_id' => $orderShipping->get_meta('wcus_settlement_ref'),
                'np_settlement_full' => $orderShipping->get_meta('wcus_settlement_full'),
                'np_street_ref' => $orderShipping->get_meta('wcus_street_ref'),
                'np_street_full' => $orderShipping->get_meta('wcus_street_full'),
            ],
        ];
    }

    public function mapAddressToOrderData(\WC_Order $order, array $address): array
    {
        $shipToDifferentAddress = get_option('woocommerce_ship_to_destination') !== 'billing_only';

        return $address['type'] === 'pudo'
            ? $this->mapPUDOAddress($address, $shipToDifferentAddress)
            : $this->mapHomeAddress($address, $shipToDifferentAddress);
    }

    private function mapPUDOAddress(array $address, bool $toDifferent): array
    {
        return [
            'delete' => [
                'shippingMeta.wcus_city_ref',
                'shippingMeta.wcus_city_name',
                'shippingMeta.wcus_warehouse_ref',
                'shippingMeta.wcus_warehouse_name',
                'shippingMeta.wcus_settlement_ref',
                'shippingMeta.wcus_settlement_full',
                'shippingMeta.wcus_settlement_name',
                'shippingMeta.wcus_settlement_area',
                'shippingMeta.wcus_settlement_region',
                'shippingMeta.wcus_street_ref',
                'shippingMeta.wcus_street_name',
                'shippingMeta.wcus_street_full',
                'shippingMeta.wcus_house',
                'shippingMeta.wcus_flat',
                'shippingMeta.wcus_api_address',
            ],
            'update' => [
                'billing_state' => '',
                'billing_city' => $address['city'],
                'billing_address_1' => $address['address_1'],
                'shipping_state' => '',
                'shipping_city' => $address['city'],
                'shipping_address_1' => $address['address_1'],
                'shippingMeta.wcus_city_ref' => $address['extra']['pudo_city_id'],
                'shippingMeta.wcus_city_name' => $address['city'],
                'shippingMeta.wcus_warehouse_ref' => $address['pudo_point_id'],
                'shippingMeta.wcus_warehouse_name' => $address['address_1'],
            ]
        ];
    }

    private function mapHomeAddress(array $address, bool $toDifferent): array
    {
        return [
            'delete' => [
                'shippingMeta.wcus_city_ref',
                'shippingMeta.wcus_city_name',
                'shippingMeta.wcus_warehouse_ref',
                'shippingMeta.wcus_warehouse_name',
                'shippingMeta.wcus_settlement_ref',
                'shippingMeta.wcus_settlement_full',
                'shippingMeta.wcus_settlement_name',
                'shippingMeta.wcus_settlement_area',
                'shippingMeta.wcus_settlement_region',
                'shippingMeta.wcus_street_ref',
                'shippingMeta.wcus_street_name',
                'shippingMeta.wcus_street_full',
                'shippingMeta.wcus_house',
                'shippingMeta.wcus_flat',
                'shippingMeta.wcus_api_address',
            ],
            'update' => [
                'billing_state' => '',
                'billing_city' => $address['city'],
                'billing_address_1' => sprintf(
                    '%s, %s%s',
                    $address['extra']['np_street_full'],
                    $address['address_2'],
                    empty($address['address_3']) ? '' : (' кв. ' . $address['address_3'])
                ),
                'shipping_state' => '',
                'shipping_city' => $address['city'],
                'shipping_address_1' => sprintf(
                    '%s, %s%s',
                    $address['extra']['np_street_full'],
                    $address['address_2'],
                    empty($address['address_3']) ? '' : (' кв. ' . $address['address_3'])
                ),
                'shippingMeta.wcus_settlement_ref' => $address['extra']['np_settlement_id'],
                'shippingMeta.wcus_settlement_full' => $address['extra']['np_settlement_full'],
                'shippingMeta.wcus_settlement_name' => $address['city'],
                'shippingMeta.wcus_settlement_area' => $address['state'],
                'shippingMeta.wcus_settlement_region' => $address['district'],
                'shippingMeta.wcus_street_ref' => $address['extra']['np_street_ref'],
                'shippingMeta.wcus_street_name' => $address['address_1'],
                'shippingMeta.wcus_street_full' => $address['extra']['np_street_full'],
                'shippingMeta.wcus_house' => $address['address_2'],
                'shippingMeta.wcus_flat' => $address['address_3'],
                'shippingMeta.wcus_api_address' => 1,
            ]
        ];
    }
}
