<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class ProductBadges implements Registrable
{
    use Loadable;

    public const META_KEY = '_maruderm_product_badge';

    private const BADGES = [
        'new' => 'Новинка',
        'bestseller' => 'Бестселер',
        'out' => 'Немає в наявності',
        'sale' => '−20%',
        'limited' => 'Лімітований',
        'low' => 'Мало в наявності',
        'featured' => 'Вибір Maruderm',
        'exclusive' => 'Онлайн-ексклюзив',
    ];

    public function register(): void
    {
        add_action('wp', [$this, 'replaceLegacyBadges'], 1);
        add_action('martfury_after_product_loop_thumbnail', [$this, 'renderLoopBadges'], 20);
        add_action('woocommerce_after_product_gallery', [$this, 'renderSingleBadge'], 20);
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderAdminField']);
        add_action('woocommerce_admin_process_product_object', [$this, 'saveAdminField']);
    }

    /** @return array{tone: string, label: string}|null */
    public function resolve(\WC_Product $product): ?array
    {
        if (!$product->is_in_stock()) {
            return $this->badge('out', $product);
        }

        $explicit_tone = sanitize_key((string) $product->get_meta(self::META_KEY, true));

        if ($explicit_tone !== 'out' && isset(self::BADGES[$explicit_tone])) {
            return $this->badge($explicit_tone, $product);
        }

        foreach (array_keys(self::BADGES) as $tone) {
            if ($tone === 'out') {
                continue;
            }

            if (has_term('maruderm-badge-' . $tone, 'product_tag', $product->get_id())) {
                return $this->badge($tone, $product);
            }
        }

        if ($product->is_on_sale()) {
            return $this->badge('sale', $product);
        }

        $stock_quantity = $product->get_stock_quantity();
        if ($product->managing_stock() && $stock_quantity !== null && $stock_quantity <= 5) {
            return $this->badge('low', $product);
        }

        if ($product->is_featured()) {
            return $this->badge('featured', $product);
        }

        if ($product->get_total_sales() >= 10) {
            return $this->badge('bestseller', $product);
        }

        $created_at = $product->get_date_created();
        $is_new = $product->get_meta('_is_new', true) === 'yes'
            || ($created_at !== null && $created_at->getTimestamp() >= strtotime('-30 days'));

        return $is_new ? $this->badge('new', $product) : null;
    }

    public function replaceLegacyBadges(): void
    {
        $this->removeCallbacksByMethod('martfury_after_product_loop_thumbnail', 'product_ribbons');
        $this->removeCallbacksByMethod('woocommerce_after_product_gallery', 'product_ribbons');
    }

    public function renderLoopBadges(): void
    {
        global $product;

        if ($product instanceof \WC_Product) {
            echo wp_kses_post($this->markup($product, 'loop'));
        }
    }

    public function renderSingleBadge(): void
    {
        global $product;

        if ($product instanceof \WC_Product) {
            echo wp_kses_post($this->markup($product, 'single'));
        }
    }

    public function renderAdminField(): void
    {
        if (!function_exists('woocommerce_wp_select')) {
            return;
        }

        woocommerce_wp_select([
            'id' => self::META_KEY,
            'label' => 'Бейдж Maruderm',
            'description' => 'Обране значення має пріоритет, крім статусу «Немає в наявності».',
            'desc_tip' => true,
            'options' => ['' => 'Автоматично'] + self::BADGES,
        ]);
    }

    public function saveAdminField(\WC_Product $product): void
    {
        $tone = isset($_POST[self::META_KEY])
            ? sanitize_key(wp_unslash((string) $_POST[self::META_KEY]))
            : '';

        if (isset(self::BADGES[$tone])) {
            $product->update_meta_data(self::META_KEY, $tone);

            return;
        }

        $product->delete_meta_data(self::META_KEY);
    }

    private function markup(\WC_Product $product, string $context): string
    {
        $badge = $this->resolve($product);

        if ($badge === null) {
            return '';
        }

        return sprintf(
            '<span class="maruderm-product-badges maruderm-product-badges--%1$s"><span class="maruderm-product-badge maruderm-product-badge--%2$s">%3$s</span></span>',
            esc_attr($context),
            esc_attr($badge['tone']),
            esc_html($badge['label'])
        );
    }

    /** @return array{tone: string, label: string} */
    private function badge(string $tone, \WC_Product $product): array
    {
        $label = self::BADGES[$tone];

        if ($tone === 'sale') {
            $percentage = $this->salePercentage($product);
            $label = $percentage > 0 ? '−' . $percentage . '%' : $label;
        }

        return ['tone' => $tone, 'label' => $label];
    }

    private function salePercentage(\WC_Product $product): int
    {
        if ($product->is_type('variable')) {
            $percentages = [];

            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);

                if (!$variation instanceof \WC_Product_Variation) {
                    continue;
                }

                $percentages[] = $this->priceDiscountPercentage($variation);
            }

            return $percentages === [] ? 0 : max($percentages);
        }

        return $this->priceDiscountPercentage($product);
    }

    private function priceDiscountPercentage(\WC_Product $product): int
    {
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();

        if ($regular_price <= 0 || $sale_price <= 0 || $sale_price >= $regular_price) {
            return 0;
        }

        return (int) round((($regular_price - $sale_price) / $regular_price) * 100);
    }

    private function removeCallbacksByMethod(string $hook_name, string $method_name): void
    {
        global $wp_filter;

        if (empty($wp_filter[$hook_name]) || !$wp_filter[$hook_name] instanceof \WP_Hook) {
            return;
        }

        foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;

                if (!is_array($function) || ($function[1] ?? null) !== $method_name) {
                    continue;
                }

                remove_action($hook_name, $function, (int) $priority);
            }
        }
    }
}
