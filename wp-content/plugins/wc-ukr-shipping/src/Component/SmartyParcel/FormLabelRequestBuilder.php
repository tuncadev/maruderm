<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\SmartyParcel;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUSCore\Http\Request;

class FormLabelRequestBuilder implements LabelRequestBuilderInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function build(): array
    {
        $request = $this->request;
        $labelRequest = [
            'carrier_account_id' => $request->get('sender')['carrier_account_id'],
            'billing' => [
                'paid_by' => strtolower($request->get('ttn')['payer_type']),
                'payment_method' => $request->get('ttn')['payment_method'] === 'NonCash'
                    ? 'card'
                    : 'cash',
            ],
            'shipment' => [
                'ship_date' => $request->get('ttn')['date'],
            ]
        ];

        $order = wc_get_order((int)$request->get('ttn')['order_id']);
        if ($order && $order->get_meta('_smartyparcel_order_id')) {
            $labelRequest['order_id'] = $order->get_meta('_smartyparcel_order_id');
        }

        $shipTo = [
            'name' => sprintf(
                '%s %s%s',
                WCUSHelper::prepareApiString($request->get('recipient')['firstname']),
                WCUSHelper::prepareApiString($request->get('recipient')['lastname']),
                $request->get('recipient')['middlename']
                    ? ' ' . WCUSHelper::prepareApiString($request->get('recipient')['middlename'])
                    : '',
            ),
            'phone' => WCUSHelper::preparePhone($request->get('recipient')['phone']),
            'email' => $request->get('recipient')['email'] ?? null,
        ];
        if ($request->get('recipient')['service_type'] === 'Warehouse') {
            $shipTo['carrier_city_id'] = $request->get('recipient')['city_ref'];
            $shipTo['carrier_warehouse_id'] = $request->get('recipient')['warehouse_ref'];
        } else {
            $shipTo['country_code'] = 'UA';
            $shipTo['city'] = WCUSHelper::prepareApiString($request->get('recipient')['settlement_name']);
            $shipTo['state'] = WCUSHelper::prepareApiString($request->get('recipient')['settlement_area']);
            $shipTo['district'] = WCUSHelper::prepareApiString($request->get('recipient')['settlement_region']);
            $shipTo['address_1'] = WCUSHelper::prepareApiString($request->get('recipient')['street_name']);
            $shipTo['address_2'] = $request->get('recipient')['house'];
            $shipTo['address_3'] = $request->get('recipient')['flat'];
            if (!empty($request->get('recipient')['address_instructions'])) {
                $shipTo['instructions'] = $request->get('recipient')['address_instructions'];
            }
        }

        // Check for organization
        if ($request->get('recipient')['type'] === 'organization') {
            $shipTo['company_name'] = WCUSHelper::prepareApiString(
                $request->get('recipient')['organization_name'] ?? 'Unknown company'
            );
            $shipTo['tax_ids'] = [
                [
                    'type' => 'edrpou',
                    'number' => $request->get('recipient')['organization_edrpou'],
                    'country' => 'UA',
                ]
            ];
        }
        $labelRequest['shipment']['ship_to'] = $shipTo;
        $labelRequest['shipment']['ship_from_address_id'] = $request->get('sender')['selected_address_id'];

        // Parcels
        $parcels = [];
        if (
            $request->get('ttn')['global_params'] === 'true'
            && $request->get('ttn')['isPoshtomatDelivery'] === 'false'
        ) {
            $parcel = [
                'insurance_cost' => $request->get('ttn')['cost'],
                'weight' => [
                    'value' => (float)$request->get('ttn')['weight'],
                    'unit' => 'kg',
                ],
                'description' => $request->get('ttn')['description'],
            ];
            if ((float)$request->get('ttn')['volumetric_weight'] > 0) {
                $parcel['volumetric_weight'] = (float)$request->get('ttn')['volumetric_weight'];
            }
            $parcels[] = $parcel;

            $labelRequest['custom_fields']['np_seats_amount'] = (int)$request->get('ttn')['seats_amount'];
        } else {
            foreach ($request->get('ttn')['seats'] as $index => $seat) {
                $parcels[] = [
                    'insurance_cost' => $index === 0 ? $request->get('ttn')['cost'] : 0,
                    'weight' => [
                        'value' => (float)$seat['weight'],
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'width' => (int)$seat['width'],
                        'height' => (int)$seat['height'],
                        'length' => (int)$seat['length'],
                        'unit' => 'cm',
                    ],
                    'description' => $index === 0 ? $request->get('ttn')['description'] : '-',
                ];
            }
        }
        $labelRequest['shipment']['parcels'] = $parcels;

        if (!empty($request->get('ttn')['barcode'])) {
            $labelRequest['shipment']['external_order_id'] =  $request->get('ttn')['barcode'];
        }
        if (!empty($request->get('ttn')['additional'])) {
            $labelRequest['custom_fields']['additional_information'] =  $request->get('ttn')['additional'];
        }

        // Payment Control and COD
        if ($request->get('ttn')['payment_control'] === '1') {
            $labelRequest['service_options']['cod'] = [
                'payment_method' => 'cash_equivalent',
                'value' => [
                    'amount' => (float)$request->get('ttn')['payment_control_cost'],
                    'currency' => 'UAH',
                ],
            ];
        } elseif ($request->get('ttn')['backward_delivery'] === '1') {
            $labelRequest['service_options']['cod'] = [
                'payment_method' => 'cash',
                'value' => [
                    'amount' => (float)$request->get('ttn')['backward_delivery_cost'],
                    'currency' => 'UAH',
                ],
                'options' => [
                    'nova_poshta_cod_payer' => $request->get('ttn')['backward_delivery_payer'] === 'Sender'
                        ? 'sender'
                        : 'recipient',
                ]
            ];
        }

        return $labelRequest;
    }
}
