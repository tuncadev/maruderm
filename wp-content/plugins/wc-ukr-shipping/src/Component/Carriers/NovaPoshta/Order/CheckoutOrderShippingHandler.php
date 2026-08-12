<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\Order;

use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;
use kirillbdev\WCUkrShipping\Contracts\Order\OrderShippingHandlerInterface;
use kirillbdev\WCUkrShipping\Traits\SafeOrderShippingTrait;

class CheckoutOrderShippingHandler implements OrderShippingHandlerInterface
{
    use SafeOrderShippingTrait;

    private string $fieldGroup;

    private CustomerStorageInterface $customerStorage;

    public function __construct(string $fieldGroup)
    {
        $this->fieldGroup = $fieldGroup;
        $this->customerStorage = wcus_container()->make(CustomerStorageInterface::class);
    }

    public function saveShippingData(\WC_Order_Item_Shipping $item, array $data): void
    {
        $this->customerStorage->remove(CustomerStorageInterface::KEY_LAST_CITY_REF);
        $this->customerStorage->remove(CustomerStorageInterface::KEY_LAST_WAREHOUSE_REF);

        $addressKey = 'wcus_np_' . $this->fieldGroup . '_custom_address_active';
        if (isset($data[$addressKey]) && (int)$data[$addressKey] === 1) {
            $this->saveAddressShipping($item, $data);
        } else {
            $this->saveWarehouseShipping($item, $data);
        }
    }

    private function saveAddressShipping(\WC_Order_Item_Shipping $item, array $data): void
    {
        $settlementRef = $data['wcus_np_' . $this->fieldGroup . '_settlement_ref'] ?? '';
        $settlementFull = $data['wcus_np_' . $this->fieldGroup . '_settlement_full'] ?? '';
        $settlementName = $data['wcus_np_' . $this->fieldGroup . '_settlement_name'] ?? '';
        $settlementArea = $data['wcus_np_' . $this->fieldGroup . '_settlement_area'] ?? '';
        $settlementRegion = $data['wcus_np_' . $this->fieldGroup . '_settlement_region'] ?? '';

        $streetRef = $data['wcus_np_' . $this->fieldGroup . '_street_ref'] ?? '';
        $streetName = $data['wcus_np_' . $this->fieldGroup . '_street_name'] ?? '';
        $streetFull = $data['wcus_np_' . $this->fieldGroup . '_street_full'] ?? '';

        $house = $data['wcus_np_' . $this->fieldGroup . '_house'] ?? '';
        $flat = $data['wcus_np_' . $this->fieldGroup . '_flat'] ?? '';

        $this->updateMeta($item, 'wcus_settlement_ref', $settlementRef);
        $this->updateMeta($item, 'wcus_settlement_full', $settlementFull);
        $this->updateMeta($item, 'wcus_settlement_name', $settlementName);
        $this->updateMeta($item, 'wcus_settlement_area', $settlementArea);
        $this->updateMeta($item, 'wcus_settlement_region', $settlementRegion);

        $this->updateMeta($item, 'wcus_street_ref', $streetRef);
        $this->updateMeta($item, 'wcus_street_name', $streetName);
        $this->updateMeta($item, 'wcus_street_full', $streetFull);

        $this->updateMeta($item, 'wcus_house', $house);
        $this->updateMeta($item,'wcus_flat', $flat);

        $item->update_meta_data('wcus_api_address', 1);

        // todo: This logic may be attached through event model
        $this->customerStorage->add(CustomerStorageInterface::KEY_LAST_SETTLEMENT, [
            'full' => $this->sanitizeValue($settlementFull),
            'ref' => $this->sanitizeValue($settlementRef),
            'name' => $this->sanitizeValue($settlementName),
            'area' => $this->sanitizeValue($settlementArea),
            'region' => $this->sanitizeValue($settlementRegion)
        ]);
        $this->customerStorage->add(CustomerStorageInterface::KEY_LAST_STREET, [
            'full' => $this->sanitizeValue($streetFull),
            'ref' => $this->sanitizeValue($streetRef),
            'name' => $this->sanitizeValue($streetName)
        ]);
        $this->customerStorage->add(CustomerStorageInterface::KEY_LAST_HOUSE, $this->sanitizeValue($house));
        $this->customerStorage->add(CustomerStorageInterface::KEY_LAST_FLAT, $this->sanitizeValue($flat));
    }

    private function saveWarehouseShipping(\WC_Order_Item_Shipping $item, array $data): void
    {
        $cityRef = $data['wcus_np_' . $this->fieldGroup . '_city'] ?? '';
        $warehouseRef = $data['wcus_np_' . $this->fieldGroup . '_warehouse'] ?? '';

        $this->updateMeta($item, 'wcus_city_ref', $cityRef);
        $this->updateMeta($item, 'wcus_city_name', $data['wcus_np_' . $this->fieldGroup . '_city_name'] ?? '-');
        $this->updateMeta($item, 'wcus_warehouse_ref', $warehouseRef);
        $this->updateMeta($item, 'wcus_warehouse_name', $data['wcus_np_' . $this->fieldGroup . '_warehouse_name'] ?? '-');

        $this->customerStorage->add(CustomerStorageInterface::KEY_LAST_CITY_REF, sanitize_text_field($cityRef));
        $this->customerStorage->add(CustomerStorageInterface::KEY_LAST_WAREHOUSE_REF, sanitize_text_field($warehouseRef));
    }
}
