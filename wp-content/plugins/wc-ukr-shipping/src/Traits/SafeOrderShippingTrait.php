<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Traits;

trait SafeOrderShippingTrait
{
    private function sanitizeValue(string $value): string
    {
        return sanitize_text_field(wp_unslash($value));
    }

    /**
     * @param \WC_Order_Item_Shipping $item
     * @param string $key
     * @param mixed $value
     */
    private function updateMeta(\WC_Order_Item_Shipping $item, string $key, $value): void
    {
        $item->update_meta_data($key, $this->sanitizeValue($value));
    }
}
