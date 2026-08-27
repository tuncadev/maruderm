<?php

declare(strict_types=1);

namespace Maruderm\HairAnalysis;

use Maruderm\WooCommerce\ProductImageRepository;

if (!defined('ABSPATH')) {
    exit();
}

/** Supplies live product imagery to the canonical hair diagnostic template. */
final class HairAnalysisRenderer
{
    private ProductImageRepository $images;

    public function __construct(?ProductImageRepository $images = null)
    {
        $this->images = $images ?? new ProductImageRepository();
    }

    public function render(): void
    {
        $heroImages = [
            $this->productImage(6013),
            $this->productImage(6009),
        ];
        $template = get_theme_file_path('templates/hair-analysis.php');

        if (file_exists($template)) {
            include $template;
        }
    }

    private function productImage(int $productId): string
    {
        $product = function_exists('wc_get_product') ? wc_get_product($productId) : false;

        if ($product instanceof \WC_Product) {
            return $this->images->primaryUrl($product);
        }

        return function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : '';
    }
}
