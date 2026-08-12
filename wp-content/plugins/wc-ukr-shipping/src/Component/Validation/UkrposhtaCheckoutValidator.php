<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Validation;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

class UkrposhtaCheckoutValidator implements CheckoutValidatorInterface
{
    public function validate(array $data): void
    {
        $this->validateWarehouseShipping(WCUSHelper::getCheckoutFieldGroup($data), $data);
    }

    private function validateWarehouseShipping(string $type, array $data): void
    {
        if (empty($data['wcus_ukrposhta_' . $type . '_city'])
            || empty($data['wcus_ukrposhta_' . $type . '_warehouse'])
        ) {
            wc_add_notice(
                __('Select Ukrposhta warehouse', 'wc-ukr-shipping-i18n'),
                'error'
            );
        }
    }
}
