<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\SmartyParcel;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Http\Resources\OrderResource;

class OrderLabelRequestBuilder implements LabelRequestBuilderInterface
{
    private \WC_Order $order;
    private SmartyParcelWPApi $api;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
        $this->api = wcus_container()->make(SmartyParcelWPApi::class);
    }

    public function build(): array
    {
        $order = $this->order;
        $prepared = $this->prepareLabelRequest();

        if ($prepared === null) {
            throw new \LogicException('Unable to prepare label request');
        }

        $labelRequest = $prepared['result'];

        if ($order->get_meta('_smartyparcel_order_id')) {
            $labelRequest['order_id'] = $order->get_meta('_smartyparcel_order_id');
        }

        $additional = apply_filters('wcus_ttn_form_additional', '', $order);
        if (!empty($additional)) {
            $labelRequest['custom_fields']['additional_information'] = $additional;
        }

        return $labelRequest;
    }

    private function prepareLabelRequest(): ?array
    {
        try {
            $orderPayload = (new OrderResource($this->order))->toArray();

            return $this->api->sendRequest('/v1/labels/prepare', [
                'order' => $orderPayload,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
