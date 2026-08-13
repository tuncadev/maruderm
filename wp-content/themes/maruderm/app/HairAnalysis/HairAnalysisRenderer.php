<?php

declare(strict_types=1);

namespace Maruderm\HairAnalysis;

if (!defined('ABSPATH')) {
    exit();
}

/** Supplies live product imagery to the canonical hair diagnostic template. */
final class HairAnalysisRenderer
{
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
            $image = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_single');

            if (is_string($image)) {
                return $image;
            }
        }

        return function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : '';
    }
}
