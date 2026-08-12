<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\Order;

use kirillbdev\WCUkrShipping\Contracts\Order\OrderHandlerInterface;
use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\AreaRepositoryInterface;
use kirillbdev\WCUkrShipping\Services\TranslateService;

class CheckoutOrderHandler implements OrderHandlerInterface
{
    private AreaRepositoryInterface $areaRepository;
    private NovaPoshtaRepository $repository;
    private TranslateService $translateService;

    private string $fieldGroup;

    public function __construct()
    {
        $this->repository = new NovaPoshtaRepository();
        $this->translateService = new TranslateService();
        $this->areaRepository = wcus_container()->make(AreaRepositoryInterface::class);
    }

    public function saveShippingData(\WC_Order $order, array $data): void
    {
        // Init checkout configuration
        $isShipToDifferentAddress = $this->isShipToDifferentAddress($data);
        $this->fieldGroup =  $isShipToDifferentAddress ? 'shipping' : 'billing';

        if ($isShipToDifferentAddress) {
            $order->set_shipping_phone(sanitize_text_field($data['wcus_shipping_phone'] ?? ''));
            $order->update_meta_data('wcus_shipping_phone', sanitize_text_field($data['wcus_shipping_phone'] ?? ''));
            $order->update_meta_data('_wcus_ship_to_different_address', 1);
        } else {
            $order->set_shipping_first_name($order->get_billing_first_name());
            $order->set_shipping_last_name($order->get_billing_last_name());
        }

        $addressKey = 'wcus_np_' . $this->fieldGroup . '_custom_address_active';
        if (isset($data[$addressKey]) && (int)$data[$addressKey] === 1) {
            $this->saveAddressShipping($order, $data);
        } else {
            $this->saveWarehouseShipping($order, $data);
        }

        $order->update_meta_data('wcus_data_version', '3');
        $order->save();
    }

    /**
     * @throws \WC_Data_Exception
     */
    public function setState(\WC_Order $order, string $state): void
    {
        $state = sanitize_text_field(wp_unslash($state));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $order->set_billing_state($state);
        } else {
            $order->set_shipping_state($state);
        }
    }

    /**
     * @throws \WC_Data_Exception
     */
    public function setCity(\WC_Order $order, string $city): void
    {
        $city = sanitize_text_field(wp_unslash($city));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $order->set_billing_city($city);
        } else {
            $order->set_shipping_city($city);
        }
    }

    /**
     * @throws \WC_Data_Exception
     */
    public function setAddress(\WC_Order $order, string $address): void
    {
        $address = sanitize_text_field(wp_unslash($address));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $order->set_billing_address_1($address);
        } else {
            $order->set_shipping_address_1($address);
        }
    }

    private function saveWarehouseShipping(\WC_Order $order, array $data): void
    {
        $this->saveArea($order, $data);
        $this->saveCity($order, $data);
        $this->saveWarehouse($order, $data);
    }

    private function saveAddressShipping(\WC_Order $order, array $data): void
    {
        $settlement = $data['wcus_np_' . $this->fieldGroup . '_settlement_full'] ?? '';
        $street = $data['wcus_np_' . $this->fieldGroup . '_street_full'] ?? '';
        $house = $data['wcus_np_' . $this->fieldGroup . '_house'] ?? '';
        $flat = $data['wcus_np_' . $this->fieldGroup . '_flat'] ?? '';

        $this->setCity($order, $settlement);
        $this->setAddress(
            $order,
            sprintf(
                '%s, %s%s',
                $street,
                $house,
                empty($flat) ? '' : (' кв. ' . $flat)
            )
        );
    }

    private function saveArea(\WC_Order $order, array $data): void
    {
        $cityRef = $data['wcus_np_' . $this->fieldGroup . '_city'] ?? '';
        $city = $this->repository->getCityByRef($cityRef);

        if ($city) {
            $area = $this->areaRepository->findByRef($city['area_ref']);
            if ($area !== null) {
                $this->setState(
                    $order,
                    $this->translateService->getCurrentLanguage() === 'ua' ? $area->getNameUa() : $area->getNameRu()
                );
            }
        }
    }

    private function saveCity(\WC_Order $order, array $data): void
    {
        $cityName = $data['wcus_np_' . $this->fieldGroup . '_city_name'] ?? '';
        if ($cityName) {
            $this->setCity($order, $cityName);
        }
    }

    private function saveWarehouse(\WC_Order $order, array $data): void
    {
        $warehouseName = $data['wcus_np_' . $this->fieldGroup . '_warehouse_name'] ?? '';
        if ($warehouseName) {
            $this->setAddress($order, $warehouseName);
        }
    }

    private function isShipToDifferentAddress(array $data): bool
    {
        return isset($data['ship_to_different_address'])
            && (int)$data['ship_to_different_address'] === 1;
    }
}
