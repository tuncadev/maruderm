<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services\Calculation;

use kirillbdev\WCUkrShipping\Model\OrderProduct;

class ProductDimensionService
{
    /**
     * @param OrderProduct[] $products
     * @return float
     */
    public function getTotalWeight(array $products): float
    {
        $defaultWeight = wc_ukr_shipping_get_option('wcus_ttn_weight_default') ?: 0.1;
        $weight = 0;
        foreach ($products as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        return round(max($weight, (float)$defaultWeight), 2);
    }

    /**
     * @param OrderProduct[] $products
     * @return array
     */
    public function getTotalDimensions(array $products): array
    {
        $defaultWidth = wc_ukr_shipping_get_option('wcus_ttn_width_default');
        $defaultHeight = wc_ukr_shipping_get_option('wcus_ttn_height_default');
        $defaultLength = wc_ukr_shipping_get_option('wcus_ttn_length_default');

        $width = $height = $length = 0;
        foreach ($products as $product) {
            if ($product->getWidth() <= 0 || $product->getHeight() <= 0 || $product->getLength() <= 0) {
                $width = $defaultWidth;
                $height = $defaultHeight;
                $length = $defaultLength;
                break;
            }

            $width = $product->getWidth() > $width ? $product->getWidth() : $width;
            $height = $product->getHeight() > $height ? $product->getHeight() : $height;
            $length = $product->getLength() > $length ? $product->getLength() : $length;
        }

        return [
            'width' => $width,
            'height' => $height,
            'length' => $length,
        ];
    }

    /**
     * @param OrderProduct[] $products
     * @return float
     */
    public function calculateTotalVolumeWeight(array $products): float
    {
        $defaultWidth = wc_ukr_shipping_get_option('wcus_ttn_width_default');
        $defaultHeight = wc_ukr_shipping_get_option('wcus_ttn_height_default');
        $defaultLength = wc_ukr_shipping_get_option('wcus_ttn_length_default');
        $volumeWeight = 0;

        foreach ($products as $product) {
            $width = $product->getWidth() > 0 ? $product->getWidth() : $defaultWidth;
            $height = $product->getHeight() > 0 ? $product->getHeight() : $defaultHeight;
            $length = $product->getLength() > 0 ? $product->getLength() : $defaultLength;
            $volumeWeight += $width * $height * $length / 4000 * $product->getQuantity();
        }

        return $volumeWeight ? round($volumeWeight, 2) : 0.1;
    }
}
