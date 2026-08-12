<?php

declare(strict_types=1);

namespace Maruderm\LandingPage;

if (!defined('ABSPATH')) {
    exit();
}

final class LandingPageCatalog
{
    /**
     * @return \WP_Term[]
     */
    public function categories(int $limit = 6): array
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0,
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC',
        ]);

        return is_wp_error($terms) ? [] : $terms;
    }

    /**
     * @param int[] $exclude
     * @return \WC_Product[]
     */
    public function products(string $collection, int $limit = 8, array $exclude = []): array
    {
        if (!function_exists('wc_get_products')) {
            return [];
        }

        $args = [
            'status' => 'publish',
            'limit' => $limit,
            'return' => 'objects',
            'exclude' => $exclude,
            'stock_status' => 'instock',
        ];

        if ($collection === 'popular') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'total_sales';
            $args['order'] = 'DESC';
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        $products = wc_get_products($args);

        return array_values(array_filter(
            $products,
            static fn ($product): bool => $product instanceof \WC_Product
        ));
    }

    public function heroProduct(): ?\WC_Product
    {
        $products = $this->products('latest', 1);

        return $products[0] ?? null;
    }

    public function categoryImage(\WP_Term $category, string $size = 'medium_large'): string
    {
        $image_id = (int) get_term_meta($category->term_id, 'thumbnail_id', true);
        $url = $image_id > 0 ? wp_get_attachment_image_url($image_id, $size) : false;

        return is_string($url) ? $url : wc_placeholder_img_src($size);
    }

    public function categoryUrl(\WP_Term $category): string
    {
        $url = get_term_link($category);

        return is_wp_error($url) ? wc_get_page_permalink('shop') : $url;
    }
}
