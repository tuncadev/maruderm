<?php

declare(strict_types=1);

namespace Maruderm\LandingPage;

if (!defined('ABSPATH')) {
    exit();
}

final class LandingPageCatalog
{
    /** @var array<int, int> */
    private array $inStockCategoryCounts = [];

    /**
     * @return \WP_Term[]
     */
    public function categories(int $limit = 6, array $include = []): array
    {
        $include = array_values(array_unique(array_filter(array_map('absint', $include))));
        $args = [
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 0,
        ];

        if ($include !== []) {
            $args['include'] = $include;
            $args['orderby'] = 'include';
        }

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return [];
        }

        $available_terms = array_values(array_filter(
            $terms,
            fn (\WP_Term $term): bool => $this->inStockProductCount($term) > 0
        ));

        return array_slice($available_terms, 0, $limit);
    }

    /**
     * @param int[] $exclude
     * @param int[] $categoryIds
     * @return \WC_Product[]
     */
    public function products(string $collection, int $limit = 8, array $exclude = [], array $categoryIds = []): array
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

        $category_ids = array_values(array_unique(array_filter(array_map('absint', $categoryIds))));

        if ($category_ids !== []) {
            $slugs = get_terms([
                'taxonomy' => 'product_cat',
                'include' => $category_ids,
                'hide_empty' => false,
                'fields' => 'slugs',
            ]);

            if (!is_wp_error($slugs) && $slugs !== []) {
                $args['category'] = $slugs;
            }
        }

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

    public function heroProduct(int $productId = 0): ?\WC_Product
    {
        if (!function_exists('wc_get_products')) {
            return null;
        }

        if ($productId > 0) {
            $selected = wc_get_product($productId);

            if (
                $selected instanceof \WC_Product
                && $selected->get_status() === 'publish'
                && $selected->is_in_stock()
            ) {
                return $selected;
            }
        }

        $products = wc_get_products([
            'status' => 'publish',
            'stock_status' => 'instock',
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]);

        return isset($products[0]) && $products[0] instanceof \WC_Product
            ? $products[0]
            : null;
    }

    public function categoryImage(\WP_Term $category, string $size = 'medium_large', int $overrideId = 0): string
    {
        $image_id = $overrideId > 0 && wp_attachment_is_image($overrideId)
            ? $overrideId
            : (int) get_term_meta($category->term_id, 'thumbnail_id', true);
        $url = $image_id > 0 ? wp_get_attachment_image_url($image_id, $size) : false;

        return is_string($url) ? $url : wc_placeholder_img_src($size);
    }

    public function categoryUrl(\WP_Term $category): string
    {
        $url = get_term_link($category);

        return is_wp_error($url) ? wc_get_page_permalink('shop') : $url;
    }

    public function inStockProductCount(\WP_Term $category): int
    {
        if (isset($this->inStockCategoryCounts[$category->term_id])) {
            return $this->inStockCategoryCounts[$category->term_id];
        }

        if (!function_exists('wc_get_products')) {
            return 0;
        }

        $result = wc_get_products([
            'status' => 'publish',
            'stock_status' => 'instock',
            'category' => [$category->slug],
            'limit' => 1,
            'paginate' => true,
            'return' => 'ids',
        ]);
        $count = is_object($result) && isset($result->total) ? (int) $result->total : 0;
        $this->inStockCategoryCounts[$category->term_id] = $count;

        return $count;
    }
}
