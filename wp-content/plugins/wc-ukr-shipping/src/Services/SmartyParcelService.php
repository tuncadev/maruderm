<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Api\SmartyParcelApi;
use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Component\SmartyParcel\LabelRequestBuilderInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\ShippingLabelsRepository;
use kirillbdev\WCUkrShipping\Dto\SmartyParcel\Labels\CreateLabelResponseDto;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;

class SmartyParcelService
{
    private SmartyParcelApi $api;
    private ShippingLabelsRepository $labelsRepository;
    private AutomationService $automationService;

    public function __construct(
        SmartyParcelApi $api,
        ShippingLabelsRepository $labelsRepository,
        AutomationService $automationService
    ) {
        $this->api = $api;
        $this->labelsRepository = $labelsRepository;
        $this->automationService = $automationService;
    }

    public function getCarrierAccounts(?string $carrierSlug = null): array
    {
        $apiKey = get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY);
        if (!$apiKey) {
            return [];
        }

        try {
            $carriers = $this->api->getCarrierAccounts($apiKey)['carriers'] ?? [];

            $result = [];
            foreach ($carriers as $carrier) {
                if ($carrierSlug === null) {
                    $result[] = $carrier;
                    continue;
                }

                if ($carrier['carrier_slug'] === $carrierSlug) {
                    $result[] = $carrier;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getAccountInfo(): ?array
    {
        if (!SmartyParcelHelper::isConnected()) {
            return null;
        }

        $accountCache = get_transient('smarty_parcel_acc');
        if (empty($accountCache)) {
            try {
                $accountCache = $this->api->getAccount(get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY));
                set_transient('smarty_parcel_acc', $accountCache, 300);
            } catch (\Throwable $e) {
                $accountCache = null;
            }
        }

        return $accountCache;
    }

    public function getRates(
        string $carrierAccountId,
        string $shipFrom,
        string $shipTo,
        string $deliveryType,
        float $declaredValue,
        float $weight,
        ?string $serviceType = null
    ): ?array {
        $apiKey = get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY);
        if (empty($apiKey)) {
            return null;
        }

        if (empty($carrierAccountId)) {
            return null;
        }

        return $this->api->estimateRates(
            $carrierAccountId,
            $shipFrom,
            $shipTo,
            $deliveryType,
            $declaredValue,
            $weight,
            $serviceType,
            (int)wc_ukr_shipping_get_option('wcus_rates_convert_currency') === 1
        );
    }

    public function createLabel(
        string $carrierSlug,
        int $orderId,
        LabelRequestBuilderInterface $builder
    ): CreateLabelResponseDto {
        $order = wc_get_order($orderId);
        if ($order === null) {
            throw new \Exception("Order $orderId not found");
        }

        $response = $this->api->createLabel($builder);
        $this->labelsRepository->create(
            $orderId,
            $response['id'],
            $response['carrier_label_id'],
            $response['tracking_number'],
            $response['carrier_slug'],
            [
                'shipment_id' => $response['shipment_id'],
                'estimated_delivery_date' => $response['estimated_delivery_date'],
            ]
        );
        $shippingLabel = $this->labelsRepository->findByOrderId($orderId);

        // Try to add to tracking
        try {
            $this->api->addTracking($response['tracking_number'], $carrierSlug);
            $this->labelsRepository->addToTracking((int)$shippingLabel['id']);
        } catch (\Throwable $e) {
            // todo: logs ?
        }

        $shippingLabel = $this->labelsRepository->findByOrderId($orderId);
        do_action('wcus_shipping_label_created', $shippingLabel, $order);

        $this->automationService->executeEvent(
            AutomationService::EVENT_LABEL_CREATED,
            new Context(
                AutomationService::EVENT_LABEL_CREATED,
                $order,
                [
                    'tracking_number' => $response['tracking_number'],
                    'carrier_status' => '',
                    'metadata' => $shippingLabel['metadata'] ?? [],
                ]
            )
        );

        return new CreateLabelResponseDto(
            (int)$shippingLabel['id'],
            $orderId,
            $response['id'],
            $response['tracking_number'],
            (float)$response['shipment_cost']['amount'],
            empty($response['estimated_delivery_date'])
                ? null
                : new \DateTimeImmutable($response['estimated_delivery_date']),
            $shippingLabel['tracking_status'] ?? ''
        );
    }

    public function attachLabel(
        string $carrierSlug,
        string $trackingNumber,
        int $orderId
    ) {
        $order = wc_get_order($orderId);
        if ($order === null) {
            throw new \Exception("Order $orderId not found");
        }

        // Check if we already have create label
        $existLabel = $this->labelsRepository->findByTrackingNumber($trackingNumber);
        if ($existLabel !== null) {
            $order->update_meta_data('_smartyparcel_label_id', (int)$existLabel['id']);
            $order->save();

            $this->automationService->executeEvent(
                AutomationService::EVENT_LABEL_ATTACHED,
                new Context(
                    AutomationService::EVENT_LABEL_CREATED,
                    $order,
                    [
                        'tracking_number' => $trackingNumber,
                        'carrier_status' => ''
                    ]
                )
            );

            return;
        }

        $this->api->addTracking($trackingNumber, $carrierSlug);

        $this->labelsRepository->attach($orderId, $trackingNumber, $carrierSlug);
        $shippingLabel = $this->labelsRepository->findByOrderId($orderId);
        $this->labelsRepository->addToTracking((int)$shippingLabel['id']);

        $this->automationService->executeEvent(
            AutomationService::EVENT_LABEL_ATTACHED,
            new Context(
                AutomationService::EVENT_LABEL_CREATED,
                $order,
                [
                    'tracking_number' => $trackingNumber,
                    'carrier_status' => ''
                ]
            )
        );
    }

    public function saveLabelFromResponse(array $response, int $orderId): void
    {
        $order = wc_get_order($orderId);
        if ($order === null) {
            throw new \Exception("Order $orderId not found");
        }

        $this->labelsRepository->create(
            $orderId,
            $response['id'],
            $response['carrier_label_id'],
            $response['tracking_number'],
            $response['carrier_slug'],
            [
                'shipment_id' => $response['shipment_id'],
                'estimated_delivery_date' => $response['estimated_delivery_date'],
            ]
        );
        $shippingLabel = $this->labelsRepository->findByOrderId($orderId);
        $this->labelsRepository->addToTracking((int)$shippingLabel['id']);

        $shippingLabel = $this->labelsRepository->findByOrderId($orderId);
        do_action('wcus_shipping_label_created', $shippingLabel, $order);

        $this->automationService->executeEvent(
            AutomationService::EVENT_LABEL_CREATED,
            new Context(
                AutomationService::EVENT_LABEL_CREATED,
                $order,
                [
                    'tracking_number' => $response['tracking_number'],
                    'carrier_status' => '',
                    'metadata' => $shippingLabel['metadata'] ?? [],
                ]
            )
        );
    }

    public function tryDisconnectApplication(): void
    {
        $apiKey = get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY);
        $this->api->disconnectApplication($apiKey);
    }

    public function getLabelByOrderId(int $orderId): ?array
    {
        return $this->labelsRepository->findByOrderId($orderId);
    }
}
