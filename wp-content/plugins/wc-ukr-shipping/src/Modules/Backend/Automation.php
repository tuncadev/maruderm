<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label\BatchLabelRequestAdapter;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label\PurchaseLabelDataCollector;
use kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label\UkrposhtaBatchLabelRequestBuilder;
use kirillbdev\WCUkrShipping\Component\SmartyParcel\OrderLabelRequestBuilder;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Exceptions\SmartyParcel\SmartyParcelErrorException;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Http\Resources\OrderResource;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

class Automation implements ModuleInterface
{
    private AutomationService $automationService;
    private SmartyParcelService $smartyParcelService;
    private SmartyParcelWPApi $smartyParcelApi;

    public function __construct(
        AutomationService $automationService,
        SmartyParcelService $smartyParcelService,
        SmartyParcelWPApi $smartyParcelApi
    ) {
        $this->automationService = $automationService;
        $this->smartyParcelService = $smartyParcelService;
        $this->smartyParcelApi = $smartyParcelApi;
    }

    public function init(): void
    {
        add_action( 'woocommerce_order_status_changed', [$this, 'fireUpdateOrderAutomation'], 10, 3);
        add_action('wcus_smartyparcel_auto_create_label', [$this, 'autoCreateLabel']);
        add_action('wcus_smartyparcel_sync_order', [$this, 'syncOrder']);
    }

    public function fireUpdateOrderAutomation(int $orderId, string $fromStatus, string $toStatus): void
    {
        $order = wc_get_order($orderId);
        if (!$order || $order->get_type() !== 'shop_order') {
            return;
        }

        $this->automationService->executeEvent(
                AutomationService::EVENT_ORDER_STATUS_CHANGED,
            new Context(
                AutomationService::EVENT_ORDER_STATUS_CHANGED,
                wc_get_order($orderId),
                []
            )
        );
    }

    public function autoCreateLabel(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        /** @var \WC_Order $order */
        if ($this->smartyParcelService->getLabelByOrderId($order->get_id()) !== null) {
            return;
        }

        try {
            $carrierSlug = SmartyParcelHelper::getOrderCarrierSlug($order);
            $builder = null;
            switch ($carrierSlug) {
                case CarrierSlug::NOVA_POSHTA:
                    $builder = new OrderLabelRequestBuilder($order);
                    break;
                case CarrierSlug::UKRPOSHTA:
                    $builder = new UkrposhtaBatchLabelRequestBuilder($order);
                    break;
                case CarrierSlug::ROZETKA_DELIVERY:
                    $builder = new BatchLabelRequestAdapter(
                        new PurchaseLabelDataCollector($order)
                    );
                break;
            }

            if ($builder !== null) {
                $this->smartyParcelService->createLabel(
                    $carrierSlug,
                    $order->get_id(),
                    $builder
                );
            }
        } catch (SmartyParcelErrorException $e) {
            $order->add_meta_data(
                '_wcus_automation_error',
                sprintf(
                    '[Automation] Error creating label: Source: SmartyParcel | Error: [%d] %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            $order->save_meta_data();
        } catch (\Throwable $e) {
            $order->add_meta_data(
                '_wcus_automation_error',
                sprintf('[Automation] Error creating label: Source: Internal | Error: %s', $e->getMessage())
            );
            $order->save_meta_data();
        }
    }

    public function syncOrder(int $orderId): void
    {
        if (!SmartyParcelHelper::isConnected()) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        try {
            $orderPayload = (new OrderResource($order))->toArray();
            $response = $this->smartyParcelApi->sendRequest('/v1/orders', $orderPayload);

            $order->add_meta_data('_smartyparcel_order_id', $response['id']);
            $order->save_meta_data();
        } catch (\Throwable $e) {
            // Do nothing yet
        }
    }
}
