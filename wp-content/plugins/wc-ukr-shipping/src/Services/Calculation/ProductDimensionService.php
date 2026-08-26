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
        $defaultWeight = $this->getDefaultWeight();
        $weight = 0;
        foreach ($products as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        return round(max($weight, (float)$defaultWeight), 2);
    }

    /**
     * @param OrderProduct[] $products
     * @param bool $applyDefaults If true will return default dimensions if any of the products has invalid dimensions
     * @return array|null
     */
    public function getTotalDimensions(array $products, bool $applyDefaults = true): ?array
    {
        $width = $height = $length = 0;
        foreach ($products as $product) {
            if ($product->getWidth() <= 0 || $product->getHeight() <= 0 || $product->getLength() <= 0) {
                return $applyDefaults ? $this->getDefaultDimensions() : null;
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
     * Default parcel weight, used when the order products have no weight set.
     *
     * @return float
     */
    public function getDefaultWeight(): float
    {
        return (float)apply_filters('wcus_default_parcel_weight', (float)WCUS_DEFAULT_PARCEL_WEIGHT);
    }

    /**
     * Default parcel dimensions, used when any of the order products has invalid dimensions.
     *
     * @return array
     */
    public function getDefaultDimensions(): array
    {
        return (array)apply_filters('wcus_default_parcel_dimensions', [
            'width' => WCUS_DEFAULT_PARCEL_WIDTH,
            'height' => WCUS_DEFAULT_PARCEL_HEIGHT,
            'length' => WCUS_DEFAULT_PARCEL_LENGTH,
        ]);
    }
}
