<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Validation;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

class NovaPoshtaCheckoutValidator implements CheckoutValidatorInterface
{
    public function validate(array $data): void
    {
        $type = WCUSHelper::getCheckoutFieldGroup($data);

        if ($this->maybeAddressShippingSelected($type, $data)) {
            $this->validateAddressShipping($type, $data);
        } else {
            $this->validateWarehouseShipping($type, $data);
        }
    }

    private function maybeAddressShippingSelected(string $type, array $data): bool
    {
        return isset($data['wcus_np_' . $type . '_custom_address_active'])
            && 1 === (int)$data['wcus_np_' . $type . '_custom_address_active'];
    }

    private function validateAddressShipping(string $type, array $data): void
    {
        if (empty($data['wcus_np_' . $type . '_settlement_name'])
            || empty($data['wcus_np_' . $type . '_street_name'])
            || empty($data['wcus_np_' . $type . '_house'])) {
            $this->addErrorNotice(__('Enter shipping address of Nova Poshta', 'wc-ukr-shipping-i18n'));
        }
    }

    private function validateWarehouseShipping(string $type, array $data): void
    {
        if (empty($data['wcus_np_' . $type . '_city'])
            || empty($data['wcus_np_' . $type . '_warehouse'])
        ) {
            $this->addErrorNotice(__('Select warehouse of Nova Poshta', 'wc-ukr-shipping-i18n'));
        }
    }

    private function addErrorNotice(string $message): void
    {
        wc_add_notice($message, 'error');
    }
}
