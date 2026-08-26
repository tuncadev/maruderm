<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Registers and manages the canonical WooCommerce product ingredients field. */
final class ProductIngredients implements Registrable
{
    use Loadable;

    public const META_KEY = 'ingredients';

    public function register(): void
    {
        add_action('init', [$this, 'registerMeta']);
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderAdminField']);
        add_action('woocommerce_admin_process_product_object', [$this, 'saveAdminField']);
    }

    public function registerMeta(): void
    {
        register_post_meta('product', self::META_KEY, [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_textarea_field',
            'auth_callback' => [$this, 'canEditIngredients'],
        ]);
    }

    public function canEditIngredients(): bool
    {
        return current_user_can('edit_products');
    }

    public function renderAdminField(): void
    {
        if (!function_exists('woocommerce_wp_textarea_input')) {
            return;
        }

        woocommerce_wp_textarea_input([
            'id' => self::META_KEY,
            'label' => 'Склад продукту',
            'description' => 'Повний склад продукту українською мовою. Відображається в блоці «Повний склад» на сторінці товару.',
            'desc_tip' => true,
            'rows' => 6,
        ]);
    }

    public function saveAdminField(\WC_Product $product): void
    {
        if (!isset($_POST[self::META_KEY])) {
            return;
        }

        $ingredients = sanitize_textarea_field(wp_unslash((string) $_POST[self::META_KEY]));

        if ($ingredients === '') {
            $product->delete_meta_data(self::META_KEY);

            return;
        }

        $product->update_meta_data(self::META_KEY, $ingredients);
    }
}
