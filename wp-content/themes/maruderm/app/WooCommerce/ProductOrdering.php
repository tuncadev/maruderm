<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Keeps unavailable products behind available products in frontend product lists. */
final class ProductOrdering implements Registrable
{
    use Loadable;

    private const LOOKUP_ALIAS = 'maruderm_stock_sort';

    public function register(): void
    {
        add_filter('posts_clauses', [$this, 'prioritizeAvailableProducts'], 100, 2);
    }

    /**
     * @param array<string, string> $clauses
     * @return array<string, string>
     */
    public function prioritizeAvailableProducts(array $clauses, \WP_Query $query): array
    {
        if (!$this->isFrontendProductQuery($query)) {
            return $clauses;
        }

        global $wpdb;

        $join = $clauses['join'] ?? '';

        if (!str_contains($join, self::LOOKUP_ALIAS)) {
            $lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
            $clauses['join'] = $join
                . " LEFT JOIN {$lookup_table} AS " . self::LOOKUP_ALIAS
                . " ON " . self::LOOKUP_ALIAS . ".product_id = {$wpdb->posts}.ID";
        }

        $stock_order = "CASE WHEN " . self::LOOKUP_ALIAS
            . ".stock_status = 'outofstock' THEN 1 ELSE 0 END ASC";
        $current_order = trim($clauses['orderby'] ?? '');

        if (!str_contains($current_order, self::LOOKUP_ALIAS . '.stock_status')) {
            $clauses['orderby'] = $stock_order
                . ($current_order !== '' ? ', ' . $current_order : '');
        }

        return $clauses;
    }

    private function isFrontendProductQuery(\WP_Query $query): bool
    {
        if (is_admin() && !wp_doing_ajax()) {
            return false;
        }

        if ($query->get('wc_query') === 'product_query') {
            return true;
        }

        $post_type = $query->get('post_type');

        return $post_type === 'product'
            || (is_array($post_type) && in_array('product', $post_type, true));
    }
}
