<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders and refreshes the canonical header mini-cart with live WooCommerce data. */
final class HeaderMiniCart implements Registrable
{
    use Loadable;

    private const FRAGMENT_SELECTOR = 'li.header-cart';

    public function register(): void
    {
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'addFragment']);
    }

    public static function render(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        echo self::markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /** @param array<string, string> $fragments
     *  @return array<string, string>
     */
    public function addFragment(array $fragments): array
    {
        if (!self::isEnabled()) {
            return $fragments;
        }

        $markup = self::markup();

        if ($markup !== '') {
            $fragments[self::FRAGMENT_SELECTOR] = $markup;
        }

        return $fragments;
    }

    private static function isEnabled(): bool
    {
        if (!function_exists('WC') || !function_exists('wc_get_cart_url')) {
            return false;
        }

        if (!function_exists('martfury_menu_extras')) {
            return true;
        }

        $extras = martfury_menu_extras();

        return is_array($extras) && in_array('cart', $extras, true);
    }

    private static function markup(): string
    {
        $cart = WC()->cart;

        if (!$cart instanceof \WC_Cart) {
            return '';
        }

        $items = self::items($cart);
        $itemCount = $cart->get_cart_contents_count();
        $isEmpty = $items === [];
        $cartUrl = wc_get_cart_url();
        $checkoutUrl = wc_get_checkout_url();
        $removeEndpoint = class_exists('WC_AJAX')
            ? \WC_AJAX::get_endpoint('remove_from_cart')
            : add_query_arg('wc-ajax', 'remove_from_cart', home_url('/'));
        $total = $isEmpty ? wc_price(0) : $cart->get_cart_subtotal();

        ob_start();
        ?>
        <li class="extra-menu-item menu-item-cart woocommerce header-cart" data-header-cart data-header-cart-remove-endpoint="<?php echo esc_url($removeEndpoint); ?>" data-header-cart-url="<?php echo esc_url($cartUrl); ?>">
            <a class="header-cart__trigger" href="<?php echo esc_url($cartUrl); ?>" aria-label="Кошик" aria-haspopup="true" aria-expanded="false" aria-controls="header-mini-cart" data-header-cart-toggle>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path></svg>
                <span class="action-count"><?php echo esc_html((string) $itemCount); ?></span>
            </a>
            <aside class="header-cart__dropdown" id="header-mini-cart" data-header-cart-dropdown aria-labelledby="header-mini-cart-title" hidden>
                <header class="header-cart__heading">
                    <strong id="header-mini-cart-title">Ваш кошик</strong>
                    <span data-header-cart-count><?php echo esc_html(self::countLabel($itemCount)); ?></span>
                </header>
                <div class="header-cart__items" data-header-cart-items<?php echo $isEmpty ? ' hidden' : ''; ?>>
                    <?php foreach ($items as $item) : ?>
                        <article class="header-cart__item" data-header-cart-item="<?php echo esc_attr($item['key']); ?>">
                            <a class="header-cart__image" href="<?php echo esc_url($item['url']); ?>">
                                <?php echo wp_kses_post($item['image']); ?>
                            </a>
                            <div class="header-cart__item-copy">
                                <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['name']); ?></a>
                                <span><?php echo esc_html((string) $item['quantity']); ?> × <?php echo wp_kses_post($item['price']); ?></span>
                            </div>
                            <button type="button" data-header-cart-remove="<?php echo esc_attr($item['key']); ?>" data-header-cart-remove-url="<?php echo esc_url($item['removeUrl']); ?>" aria-label="<?php echo esc_attr(sprintf('Видалити %s з кошика', $item['name'])); ?>">×</button>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="header-cart__empty" data-header-cart-empty<?php echo $isEmpty ? '' : ' hidden'; ?>>У кошику поки немає товарів.</p>
                <footer class="header-cart__footer" data-header-cart-footer<?php echo $isEmpty ? ' hidden' : ''; ?>>
                    <div class="header-cart__total">
                        <span>Разом</span><strong data-header-cart-total><?php echo wp_kses_post($total); ?></strong>
                    </div>
                    <div class="header-cart__actions">
                        <a class="header-cart__button header-cart__button--secondary" href="<?php echo esc_url($cartUrl); ?>">
                            <span>Переглянути кошик</span><span aria-hidden="true">→</span>
                        </a>
                        <a class="header-cart__button header-cart__button--primary" href="<?php echo esc_url($checkoutUrl); ?>">
                            <span>Оформити замовлення</span><span aria-hidden="true">→</span>
                        </a>
                    </div>
                </footer>
            </aside>
        </li>
        <?php

        return trim((string) ob_get_clean());
    }

    /** @return array<int, array{key: string, name: string, url: string, image: string, quantity: int, price: string, removeUrl: string}> */
    private static function items(\WC_Cart $cart): array
    {
        $items = [];

        foreach ($cart->get_cart() as $cartItemKey => $cartItem) {
            $product = apply_filters('woocommerce_cart_item_product', $cartItem['data'] ?? null, $cartItem, $cartItemKey);
            $quantity = isset($cartItem['quantity']) ? (int) $cartItem['quantity'] : 0;

            if (!$product instanceof \WC_Product || !$product->exists() || $quantity < 1) {
                continue;
            }

            if (!apply_filters('woocommerce_widget_cart_item_visible', true, $cartItem, $cartItemKey)) {
                continue;
            }

            $url = apply_filters(
                'woocommerce_cart_item_permalink',
                $product->is_visible() ? $product->get_permalink($cartItem) : wc_get_cart_url(),
                $cartItem,
                $cartItemKey
            );
            $name = apply_filters('woocommerce_cart_item_name', $product->get_name(), $cartItem, $cartItemKey);
            $image = apply_filters(
                'woocommerce_cart_item_thumbnail',
                $product->get_image('woocommerce_thumbnail'),
                $cartItem,
                $cartItemKey
            );

            $items[] = [
                'key' => (string) $cartItemKey,
                'name' => wp_strip_all_tags((string) $name),
                'url' => is_string($url) && $url !== '' ? $url : wc_get_cart_url(),
                'image' => (string) $image,
                'quantity' => $quantity,
                'price' => $cart->get_product_price($product),
                'removeUrl' => wc_get_cart_remove_url((string) $cartItemKey),
            ];
        }

        return $items;
    }

    private static function countLabel(int $quantity): string
    {
        $lastTwo = $quantity % 100;
        $last = $quantity % 10;

        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return sprintf('%d товарів', $quantity);
        }

        if ($last === 1) {
            return sprintf('%d товар', $quantity);
        }

        if ($last >= 2 && $last <= 4) {
            return sprintf('%d товари', $quantity);
        }

        return sprintf('%d товарів', $quantity);
    }
}
