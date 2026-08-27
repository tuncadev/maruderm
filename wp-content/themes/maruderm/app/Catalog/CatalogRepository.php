<?php

declare(strict_types=1);

namespace Maruderm\Catalog;

if (!defined('ABSPATH')) {
    exit();
}

final class CatalogRepository
{
    private const CATEGORY_NAVIGATION_TONES = [
        'zasoby-dlya-doglyadu-za-shkiroyu' => 'skin',
        'makiyazh' => 'makeup',
        'zasoby-dlya-doglyadu-za-volossyam' => 'hair',
        'gunes-bakim-urunleri' => 'sun',
        'zasoby-dlya-doglyadu-za-tilom' => 'body',
    ];

    /** @var array<int, array<string, \WP_Term[]>> */
    private array $termCache = [];

    /** @return \WC_Product[] */
    public function products(): array
    {
        if (!function_exists('wc_get_product')) {
            return [];
        }

        global $wpdb;

        $lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
        $taxonomy_sql = '';
        $parameters = [];

        $queried = get_queried_object();

        if (
            $queried instanceof \WP_Term
            && $this->isProductTaxonomy($queried->taxonomy)
            && $queried->taxonomy !== 'product_cat'
        ) {
            $term_ids = [(int) $queried->term_id];

            if ($queried->taxonomy === 'product_cat') {
                $children = get_term_children($queried->term_id, 'product_cat');

                if (!is_wp_error($children)) {
                    $term_ids = array_merge($term_ids, array_map('intval', $children));
                }
            }

            $placeholders = implode(', ', array_fill(0, count($term_ids), '%d'));
            $taxonomy_sql = "
                AND EXISTS (
                    SELECT 1
                    FROM {$wpdb->term_relationships} relationship
                    INNER JOIN {$wpdb->term_taxonomy} taxonomy
                        ON taxonomy.term_taxonomy_id = relationship.term_taxonomy_id
                    WHERE relationship.object_id = product.ID
                        AND taxonomy.taxonomy = %s
                        AND taxonomy.term_id IN ({$placeholders})
                )";
            $parameters = array_merge([$queried->taxonomy], $term_ids);
        }

        $sql = "
            SELECT product.ID
            FROM {$wpdb->posts} product
            INNER JOIN {$lookup_table} lookup ON lookup.product_id = product.ID
            WHERE product.post_type = 'product'
                AND product.post_status = 'publish'
                AND lookup.stock_status = 'instock'
                {$taxonomy_sql}
            ORDER BY product.post_date DESC, product.ID DESC
        ";

        if ($parameters !== []) {
            $sql = $wpdb->prepare($sql, ...$parameters);
        }

        $product_ids = array_map('intval', $wpdb->get_col($sql));
        $products = array_map('wc_get_product', $product_ids);

        return array_values(array_filter(
            $products,
            static fn ($product): bool => $product instanceof \WC_Product
                && $product->is_visible()
                && $product->is_in_stock()
        ));
    }

    /** @return \WC_Product[] */
    public function productsForCurrentView(): array
    {
        $products = [];

        foreach ($this->products() as $product) {
            $products[$product->get_id()] = $product;
        }

        $category = get_queried_object();

        if (!$category instanceof \WP_Term || $category->taxonomy !== 'product_cat') {
            return array_values($products);
        }

        global $wpdb;

        $term_ids = [$category->term_id];
        $children = get_term_children($category->term_id, 'product_cat');

        if (!is_wp_error($children)) {
            $term_ids = array_merge($term_ids, array_map('intval', $children));
        }

        $placeholders = implode(', ', array_fill(0, count($term_ids), '%d'));
        $sql = $wpdb->prepare(
            "SELECT DISTINCT product.ID
            FROM {$wpdb->posts} product
            INNER JOIN {$wpdb->term_relationships} relationship
                ON relationship.object_id = product.ID
            INNER JOIN {$wpdb->term_taxonomy} taxonomy
                ON taxonomy.term_taxonomy_id = relationship.term_taxonomy_id
            WHERE product.post_type = 'product'
                AND product.post_status = 'publish'
                AND taxonomy.taxonomy = 'product_cat'
                AND taxonomy.term_id IN ({$placeholders})
            ORDER BY product.post_date DESC, product.ID DESC",
            ...$term_ids
        );

        foreach (array_map('intval', $wpdb->get_col($sql)) as $product_id) {
            $product = wc_get_product($product_id);

            if ($product instanceof \WC_Product && $product->get_catalog_visibility() !== 'hidden') {
                $products[$product->get_id()] = $product;
            }
        }

        return array_values($products);
    }

    private function isProductTaxonomy(string $taxonomy): bool
    {
        return in_array($taxonomy, ['product_cat', 'product_tag'], true)
            || str_starts_with($taxonomy, 'pa_');
    }

    /**
     * @param \WC_Product[] $products
     * @return array<int, array{value: string, label: string, count: int, depth: int, url: string, description: string}>
     */
    public function categoryOptions(array $products): array
    {
        $categories = [];

        foreach ($products as $product) {
            $product_categories = [];

            foreach ($this->terms($product, 'product_cat') as $category) {
                $category_ids = array_merge(
                    [$category->term_id],
                    get_ancestors($category->term_id, 'product_cat', 'taxonomy')
                );

                foreach ($category_ids as $category_id) {
                    $resolved = (int) $category_id === $category->term_id
                        ? $category
                        : get_term((int) $category_id, 'product_cat');

                    if ($resolved instanceof \WP_Term && $resolved->slug !== 'uncategorized') {
                        $product_categories[$resolved->term_id] = $resolved;
                    }
                }
            }

            foreach ($product_categories as $category) {
                $categories[$category->term_id] ??= [
                    'term' => $category,
                    'count' => 0,
                ];
                $categories[$category->term_id]['count']++;
            }
        }

        return $this->flattenCategoryOptions($categories);
    }

    /**
     * @return array<int, array{value: string, label: string, url: string, image: string, tone: string}>
     */
    public function navigationCategories(): array
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'hide_empty' => true,
            'slug' => array_keys(self::CATEGORY_NAVIGATION_TONES),
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        $terms_by_slug = [];

        foreach ($terms as $term) {
            if ($term instanceof \WP_Term) {
                $terms_by_slug[$term->slug] = $term;
            }
        }

        $categories = [];

        foreach (self::CATEGORY_NAVIGATION_TONES as $slug => $tone) {
            $term = $terms_by_slug[$slug] ?? null;

            if (!$term instanceof \WP_Term) {
                continue;
            }

            $url = get_term_link($term);
            $thumbnail_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
            $image = $thumbnail_id > 0
                ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail')
                : wc_placeholder_img_src('woocommerce_thumbnail');

            if (is_wp_error($url) || !is_string($image) || $image === '') {
                continue;
            }

            $categories[] = [
                'value' => $term->slug,
                'label' => $term->name,
                'url' => $url,
                'image' => $image,
                'tone' => $tone,
            ];
        }

        return $categories;
    }

    /**
     * @param \WC_Product[] $products
     * @return array<int, array{value: string, label: string, count: int}>
     */
    public function attributeOptions(array $products, string $taxonomy): array
    {
        if (!taxonomy_exists($taxonomy)) {
            return [];
        }

        $counts = [];

        foreach ($products as $product) {
            foreach ($this->terms($product, $taxonomy) as $term) {
                $counts[$term->slug] ??= [
                    'value' => $term->slug,
                    'label' => $term->name,
                    'count' => 0,
                ];
                $counts[$term->slug]['count']++;
            }
        }

        return $this->sortOptions(array_values($counts));
    }

    /** @return \WP_Term[] */
    public function topCategories(\WC_Product $product): array
    {
        $categories = [];

        foreach ($this->terms($product, 'product_cat') as $category) {
            $ancestor_ids = get_ancestors($category->term_id, 'product_cat', 'taxonomy');
            $root_id = $ancestor_ids === [] ? $category->term_id : (int) end($ancestor_ids);
            $root = $root_id === $category->term_id ? $category : get_term($root_id, 'product_cat');

            if ($root instanceof \WP_Term && $root->slug !== 'uncategorized') {
                $categories[$root->term_id] = $root;
            }
        }

        return array_values($categories);
    }

    /** @return string[] */
    public function termSlugs(\WC_Product $product, string $taxonomy): array
    {
        return array_values(array_map(
            static fn (\WP_Term $term): string => $term->slug,
            $this->terms($product, $taxonomy)
        ));
    }

    /** @return string[] */
    public function categorySlugs(\WC_Product $product): array
    {
        $slugs = [];

        foreach ($this->terms($product, 'product_cat') as $category) {
            $category_ids = array_merge(
                [$category->term_id],
                get_ancestors($category->term_id, 'product_cat', 'taxonomy')
            );

            foreach ($category_ids as $category_id) {
                $resolved = (int) $category_id === $category->term_id
                    ? $category
                    : get_term((int) $category_id, 'product_cat');

                if ($resolved instanceof \WP_Term && $resolved->slug !== 'uncategorized') {
                    $slugs[$resolved->slug] = true;
                }
            }
        }

        return array_keys($slugs);
    }

    public function initialCategory(): string
    {
        $queried = get_queried_object();

        if (!$queried instanceof \WP_Term || $queried->taxonomy !== 'product_cat') {
            return '';
        }

        return $queried->slug;
    }

    /**
     * @param array<int, array{term: \WP_Term, count: int}> $categories
     * @return array<int, array{value: string, label: string, count: int, depth: int, url: string, description: string}>
     */
    private function flattenCategoryOptions(array $categories): array
    {
        $children = [];

        foreach ($categories as $category) {
            $children[$category['term']->parent][] = $category;
        }

        foreach ($children as &$siblings) {
            usort(
                $siblings,
                static fn (array $left, array $right): int => strnatcasecmp(
                    $left['term']->name,
                    $right['term']->name
                )
            );
        }
        unset($siblings);

        $options = [];
        $append = function (int $parent_id, int $depth) use (&$append, &$options, $children): void {
            foreach ($children[$parent_id] ?? [] as $category) {
                $url = get_term_link($category['term']);
                $options[] = [
                    'value' => $category['term']->slug,
                    'label' => $category['term']->name,
                    'count' => $category['count'],
                    'depth' => $depth,
                    'url' => is_wp_error($url) ? wc_get_page_permalink('shop') : $url,
                    'description' => trim(wp_strip_all_tags($category['term']->description)),
                ];
                $append($category['term']->term_id, $depth + 1);
            }
        };
        $append(0, 0);

        return $options;
    }

    /** @return \WP_Term[] */
    private function terms(\WC_Product $product, string $taxonomy): array
    {
        $product_id = $product->get_id();

        if (isset($this->termCache[$product_id][$taxonomy])) {
            return $this->termCache[$product_id][$taxonomy];
        }

        $terms = wp_get_post_terms($product_id, $taxonomy);
        $this->termCache[$product_id][$taxonomy] = is_wp_error($terms) ? [] : $terms;

        return $this->termCache[$product_id][$taxonomy];
    }

    /**
     * @param array<int, array{value: string, label: string, count: int}> $options
     * @return array<int, array{value: string, label: string, count: int}>
     */
    private function sortOptions(array $options): array
    {
        usort(
            $options,
            static fn (array $left, array $right): int => strnatcasecmp($left['label'], $right['label'])
        );

        return $options;
    }
}
