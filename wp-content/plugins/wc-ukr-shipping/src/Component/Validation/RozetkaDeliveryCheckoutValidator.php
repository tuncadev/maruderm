<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Validation;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

class RozetkaDeliveryCheckoutValidator implements CheckoutValidatorInterface
{
    public function validate(array $data): void
    {
        $this->validateWarehouseShipping(WCUSHelper::getCheckoutFieldGroup($data), $data);
    }

    private function validateWarehouseShipping(string $type, array $data): void
    {
        if (empty($data['wcus_rozetka_' . $type . '_city'])
            || empty($data['wcus_rozetka_' . $type . '_warehouse'])
        ) {
            wc_add_notice(
                __('Select warehouse for Rozetka Delivery', 'wc-ukr-shipping-i18n'),
                'error'
            );
        }
    }
}
