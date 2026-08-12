<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Api\SmartyParcelApi;
use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label\BatchLabelRequestAdapter;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label\PurchaseLabelDataCollector;
use kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label\UkrposhtaBatchLabelRequestBuilder;
use kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label\UkrposhtaFormLabelRequestBuilder;
use kirillbdev\WCUkrShipping\Component\SmartyParcel\FormLabelRequestBuilder;
use kirillbdev\WCUkrShipping\Component\SmartyParcel\OrderLabelRequestBuilder;
use kirillbdev\WCUkrShipping\DB\Repositories\ShippingLabelsRepository;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Exceptions\SmartyParcel\SmartyParcelErrorException;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

class SmartyParcelController extends Controller
{
    private SmartyParcelApi $api;
    private SmartyParcelWPApi $spApi;
    private SmartyParcelService $smartyParcelService;
    private ShippingLabelsRepository $shippingLabelsRepository;
    private AutomationService $automationService;

    public function __construct(
        SmartyParcelApi $api,
        SmartyParcelWPApi $spApi,
        SmartyParcelService $smartyParcelService,
        ShippingLabelsRepository $shippingLabelsRepository,
        AutomationService $automationService
    ) {
        $this->api = $api;
        $this->spApi = $spApi;
        $this->smartyParcelService = $smartyParcelService;
        $this->shippingLabelsRepository = $shippingLabelsRepository;
        $this->automationService = $automationService;
    }

    public function sendApiRequest(Request $request): ResponseInterface
    {
        try {
            $data = $this->spApi->sendRequest(
                $request->get('route'),
                $request->get('payload'),
                $request->get('query', []),
                $request->get('path_parameters', []),
            );

            return $this->jsonResponse([
                'success' => true,
                'data' => $data,
            ]);
        } catch (SmartyParcelErrorException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'source' => 'smarty_parcel',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'source' => 'internal'
                ]
            ]);
        }
    }

    public function disconnect(Request $request): ResponseInterface
    {
        try {
            $this->smartyParcelService->tryDisconnectApplication();
        } catch (\Throwable $e) {
            $strategy = $request->get('payload', [])['strategy'] ?? null;
            if ($strategy !== 'force') {
                return $this->jsonResponse([
                    'success' => false,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        delete_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY);
        delete_option(WCUS_OPTION_SMARTY_PARCEL_USER_STATUS);
        delete_option('wcus_nova_poshta_default_carrier');
        delete_option('wcus_ukrposhta_default_carrier');
        delete_transient('smarty_parcel_acc');

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @deprecated
     * @param Request $request
     * @return ResponseInterface
     *
     * todo: Will be replaced by purchaseLabel
     */
    public function createShippingLabel(Request $request): ResponseInterface
    {
        try {
            $builder = null;
            if ($request->get('carrier') === 'nova_poshta') {
                $builder = new FormLabelRequestBuilder($request);
            } elseif ($request->get('carrier') === 'ukrposhta') {
                $builder = new UkrposhtaFormLabelRequestBuilder($request);
            }

            $response = $this->smartyParcelService->createLabel(
                $request->get('carrier'),
                (int)$request->get('ttn')['order_id'],
                $builder
            );

            $formats = WCUSHelper::getLabelDownloadFormats($request->get('carrier'));
            $downloads = [];
            foreach ($formats as $format => $Name) {
                $downloads[] = [
                    'format' => $Name,
                    'url' => admin_url('admin.php?page=wc_ukr_shipping_print_label&label_id=' . $response->id . '&format=' . $format),
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'id' => $response->id,
                    'tracking_number' => $response->trackingNumber,
                    'shipment_cost' => $response->shipmentCost,
                    'estimated_delivery_date' => $response->estimatedDeliveryDate !== null
                        ? $response->estimatedDeliveryDate->format('Y-m-d')
                        : null,
                    'order_url' => get_admin_url( null, 'post.php?post=' . $response->orderId . '&action=edit'),
                    'downloads' => $downloads,
                ]
            ]);
        } catch (SmartyParcelErrorException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => 0,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function attachShippingLabel(Request $request): ResponseInterface
    {
        try {
            $this->smartyParcelService->attachLabel(
                $request->get('carrierSlug'),
                $request->get('trackingNumber'),
                (int)$request->get('orderId')
            );

            return $this->jsonResponse([
                'success' => true,
            ]);
        } catch (SmartyParcelErrorException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'source' => 'smarty_parcel',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'details ' => $e->getDetails(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'source' => 'internal',
                    'code' => 0,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function createLabelBatch(Request $request): ResponseInterface
    {
        try {
            $order = wc_get_order((int)$request->get('orderId'));
            if ($order === null) {
                throw new \Exception("Order " . (int)$request->get('orderId') . " not found");
            }

            $shippingMethod = WCUSHelper::getOrderShippingMethod($order);
            if ($shippingMethod === null) {
                throw new \Exception('Unable to get order shipping method');
            }

            switch ($shippingMethod->get_method_id()) {
                case WC_UKR_SHIPPING_NP_SHIPPING_NAME:
                    $carrier = CarrierSlug::NOVA_POSHTA;
                    $builder = new OrderLabelRequestBuilder($order);
                    break;
                case WCUS_SHIPPING_METHOD_UKRPOSHTA:
                    $carrier = CarrierSlug::UKRPOSHTA;
                    $builder = new UkrposhtaBatchLabelRequestBuilder($order);
                    break;
                case WCUS_SHIPPING_METHOD_ROZETKA:
                    $carrier = CarrierSlug::ROZETKA_DELIVERY;
                    $builder = new BatchLabelRequestAdapter(new PurchaseLabelDataCollector($order));
                    break;
                default:
                    throw new \Exception('Carrier not supported for bulk operations yet');
            }

            $response = $this->smartyParcelService->createLabel(
                $carrier,
                $order->get_id(),
                $builder
            );

            $downloads = [];
            foreach (WCUSHelper::getLabelDownloadFormats($carrier) as $format => $name) {
                $downloads[] = [
                    'name' => $name,
                    'url' => admin_url('admin.php?page=wc_ukr_shipping_print_label&label_id=' . $response->id . '&format=' . $format),
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'id' => $response->id,
                    'label_id' => $response->labelId,
                    'carrier_slug' => $carrier,
                    'tracking_number' => $response->trackingNumber,
                    'shipment_cost' => $response->shipmentCost,
                    'estimated_delivery_date' => $response->estimatedDeliveryDate !== null
                        ? $response->estimatedDeliveryDate->format('Y-m-d')
                        : null,
                    'tracking_status' => $response->trackingStatus,
                    'downloads' => $downloads,
                ]
            ]);
        } catch (SmartyParcelErrorException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => 0,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function voidLabel(Request $request): ResponseInterface
    {
        try {
            $label = $this->shippingLabelsRepository->findById((int)$request->get('label_id'));
            if ($label === null) {
                throw new \Exception('Label by id ' . $request->get('label_id') . ' not found');
            }

            // We can't void legacy WCUS Pro labels or attached labels yet
            if ($label['label_id']) {
                try {
                    $this->api->voidLabel($label['label_id']);
                } catch (\Exception $e) {
                    // Do nothing yet
                }
            }

            $this->shippingLabelsRepository->deleteById((int)$label['id']);
            $this->automationService->executeEvent(
                AutomationService::EVENT_LABEL_VOIDED,
                new Context(
                    AutomationService::EVENT_LABEL_CREATED,
                    wc_get_order((int)$label['order_id']),
                    [
                        'tracking_number' => $label['tracking_number'],
                        'carrier_status' => $label['carrier_status'],
                        'metadata' => $label['metadata'] ?? [],
                    ]
                )
            );

            return $this->jsonResponse([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function saveLabel(Request $request): ResponseInterface
    {
        try {
            $this->smartyParcelService->saveLabelFromResponse(
                $request->get('response'),
                (int)$request->get('order_id')
            );

            return $this->jsonResponse([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'source' => 'internal',
                    'code' => 0,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function removeLabel(Request $request): ResponseInterface
    {
        try {
            $label = $this->shippingLabelsRepository->findById((int)$request->get('id'));
            if ($label === null) {
                throw new \Exception('Label by id ' . $request->get('label_id') . ' not found');
            }
            $this->shippingLabelsRepository->deleteById((int)$label['id']);

            $this->automationService->executeEvent(
                AutomationService::EVENT_LABEL_VOIDED,
                new Context(
                    AutomationService::EVENT_LABEL_CREATED,
                    wc_get_order((int)$label['order_id']),
                    [
                        'tracking_number' => $label['tracking_number'],
                        'carrier_status' => $label['carrier_status'],
                        'metadata' => $label['metadata'] ?? [],
                    ]
                )
            );

            return $this->jsonResponse([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
