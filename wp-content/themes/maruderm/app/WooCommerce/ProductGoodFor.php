<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Registers and manages the product-card suitability list. */
final class ProductGoodFor implements Registrable
{
    use Loadable;

    public const META_KEY = '_maruderm_good_for';
    public const MAX_ITEMS = 3;

    public function register(): void
    {
        add_action('init', [$this, 'registerMeta']);
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderAdminField']);
        add_action('woocommerce_admin_process_product_object', [$this, 'saveAdminField']);
    }

    public function registerMeta(): void
    {
        register_post_meta('product', self::META_KEY, [
            'type' => 'array',
            'single' => true,
            'default' => [],
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'maxItems' => self::MAX_ITEMS,
                ],
            ],
            'sanitize_callback' => [$this, 'sanitizeItems'],
            'auth_callback' => [$this, 'canEditProducts'],
        ]);
    }

    public function canEditProducts(): bool
    {
        return current_user_can('edit_products');
    }

    /** @return string[] */
    public function items(\WC_Product $product): array
    {
        return $this->sanitizeItems($product->get_meta(self::META_KEY, true));
    }

    /** @return string[] */
    public function sanitizeItems(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\R+/u', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $item = sanitize_text_field((string) $item);

            if ($item !== '' && !in_array($item, $items, true)) {
                $items[] = $item;
            }

            if (count($items) === self::MAX_ITEMS) {
                break;
            }
        }

        return $items;
    }

    public function renderAdminField(): void
    {
        if (!function_exists('woocommerce_wp_textarea_input')) {
            return;
        }

        $product = wc_get_product(get_the_ID());
        $value = $product instanceof \WC_Product ? implode("\n", $this->items($product)) : '';

        woocommerce_wp_textarea_input([
            'id' => self::META_KEY,
            'label' => 'Кому та коли радимо?',
            'description' => 'До трьох коротких пунктів, по одному в рядку. Відображаються на промо-стані картки товару.',
            'desc_tip' => true,
            'rows' => self::MAX_ITEMS,
            'value' => $value,
        ]);
    }

    public function saveAdminField(\WC_Product $product): void
    {
        if (!isset($_POST[self::META_KEY])) {
            return;
        }

        $items = $this->sanitizeItems(wp_unslash((string) $_POST[self::META_KEY]));

        if ($items === []) {
            $product->delete_meta_data(self::META_KEY);

            return;
        }

        $product->update_meta_data(self::META_KEY, $items);
    }
}
