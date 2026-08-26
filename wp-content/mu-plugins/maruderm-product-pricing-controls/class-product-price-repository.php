<?php
/**
 * WooCommerce product pricing persistence.
 *
 * @package Maruderm
 */

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Product_Price_Repository
{
    public const MINIMUM_PRICE_META = '_maruderm_minimum_price';

    public function cogs_enabled(): bool
    {
        return class_exists(CostOfGoodsSoldController::class)
            && function_exists('wc_get_container')
            && wc_get_container()->get(CostOfGoodsSoldController::class)->feature_is_enabled();
    }

    public function cost(WC_Product $product): ?float
    {
        if ($this->cogs_enabled()) {
            return $product->get_cogs_value();
        }

        $raw_value = get_post_meta($product->get_id(), '_cogs_total_value', true);

        return $raw_value === '' ? null : (float) $raw_value;
    }

    public function minimum(WC_Product $product): ?float
    {
        $raw_value = $product->get_meta(self::MINIMUM_PRICE_META, true, 'edit');

        return $raw_value === '' ? null : (float) $raw_value;
    }

    public function save_private_prices(WC_Product $product, ?float $cost, ?float $minimum): void
    {
        if (! $this->cogs_enabled()) {
            throw new RuntimeException('WooCommerce Cost of Goods Sold must be enabled before saving private prices.');
        }

        $product->set_cogs_value($cost);

        if ($minimum === null) {
            $product->delete_meta_data(self::MINIMUM_PRICE_META);
        } else {
            $product->update_meta_data(
                self::MINIMUM_PRICE_META,
                wc_format_decimal($minimum, wc_get_price_decimals())
            );
        }

        $product->save();
    }

    /**
     * @return array{items: WC_Product[], total: int, pages: int, page: int}
     */
    public function paginated_products(string $search, int $requested_page, int $per_page): array
    {
        $ids = wc_get_products([
            'limit' => -1,
            'return' => 'ids',
            'status' => ['publish', 'draft', 'pending', 'private'],
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        $products = [];
        $needle = function_exists('mb_strtolower') ? mb_strtolower($search) : strtolower($search);

        foreach ($ids as $id) {
            $product = wc_get_product((int) $id);
            if (! $product instanceof WC_Product || ! $this->has_direct_prices($product)) {
                continue;
            }

            if ($needle !== '' && ! $this->matches_search($product, $needle)) {
                continue;
            }

            $products[] = $product;
        }

        $total = count($products);
        $pages = max(1, (int) ceil($total / max(1, $per_page)));
        $page = min(max(1, $requested_page), $pages);

        return [
            'items' => array_slice($products, ($page - 1) * $per_page, $per_page),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    private function has_direct_prices(WC_Product $product): bool
    {
        return $product->is_type(['simple', 'external', 'variation']);
    }

    private function matches_search(WC_Product $product, string $needle): bool
    {
        $haystack = implode(' ', [
            $product->get_name('edit'),
            $product->get_sku('edit'),
            method_exists($product, 'get_global_unique_id') ? $product->get_global_unique_id('edit') : '',
            (string) $product->get_id(),
        ]);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);

        return str_contains($haystack, $needle);
    }
}
