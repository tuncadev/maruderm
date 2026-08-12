<?php

declare(strict_types=1);

namespace Maruderm\Cart;

use Maruderm\WooCommerce\ProductBadges;

if (!defined('ABSPATH')) {
    exit();
}

final class CartRenderer
{
    private const DEFAULT_FREE_SHIPPING_THRESHOLD = 1500.0;

    private ProductBadges $badges;

    public function __construct(?ProductBadges $badges = null)
    {
        $this->badges = $badges ?? new ProductBadges();
    }

    public function render(): void
    {
        $cart = WC()->cart;

        echo '<main class="maruderm-cart" data-cart-page>';
        $this->renderHero();

        if (!$cart instanceof \WC_Cart || $cart->is_empty()) {
            woocommerce_output_all_notices();
            $this->renderEmpty();
            echo '</main>';

            return;
        }

        do_action('woocommerce_before_cart');
        echo '<section class="cart-content"><form class="woocommerce-cart-form" action="' . esc_url(wc_get_cart_url()) . '" method="post" data-cart-form><div class="shell cart-layout">';
        $this->renderItems($cart);
        $this->renderSummary($cart);
        echo '</div></form></section>';
        do_action('woocommerce_after_cart');
        echo '</main>';
    }

    private function renderHero(): void
    {
        echo '<section class="cart-hero checkout-hero"><div class="shell">';
        echo '<nav class="breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><span>Кошик</span></nav>';
        echo '<div class="cart-hero__content"><div><span class="kicker">Твій ритуал майже готовий</span><h1>Кошик</h1></div>';
        echo '<ol class="checkout-steps" aria-label="Етапи оформлення"><li class="is-active"><span>01</span><strong>Кошик</strong></li><li><span>02</span><strong>Доставка</strong></li><li><span>03</span><strong>Оплата</strong></li></ol>';
        echo '</div></div></section>';
    }

    private function renderItems(\WC_Cart $cart): void
    {
        echo '<div class="cart-main"><div class="cart-main__heading"><div><span class="kicker">Обрані формули</span><h2>Твоє замовлення</h2></div>';
        echo '<span class="cart-main__count" aria-live="polite">' . esc_html($this->quantityLabel($cart->get_cart_contents_count())) . '</span></div>';
        echo '<div class="cart-items">';
        do_action('woocommerce_before_cart_contents');

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $this->renderItem($cart, $cart_item_key, $cart_item);
        }

        do_action('woocommerce_cart_contents');
        echo '</div>';
        echo '<button class="cart-update" type="submit" name="update_cart" value="1" data-cart-update>Оновити кошик</button>';
        do_action('woocommerce_cart_actions');
        wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce');
        do_action('woocommerce_after_cart_contents');
        $this->renderHelp();
        echo '</div>';
    }

    /** @param array<string, mixed> $cart_item */
    private function renderItem(\WC_Cart $cart, string $cart_item_key, array $cart_item): void
    {
        $product = apply_filters('woocommerce_cart_item_product', $cart_item['data'] ?? null, $cart_item, $cart_item_key);
        $quantity = (int) ($cart_item['quantity'] ?? 0);
        $visible = (bool) apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key);

        if (!$product instanceof \WC_Product || !$product->exists() || $quantity < 1 || !$visible) {
            return;
        }

        $product_id = (int) apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
        $product_name = (string) apply_filters('woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key);
        $permalink = (string) apply_filters('woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
        $thumbnail = (string) apply_filters('woocommerce_cart_item_thumbnail', $product->get_image('woocommerce_thumbnail'), $cart_item, $cart_item_key);
        $badge = $this->badges->resolve($product);
        $category = $this->categoryLabel($product_id);
        $maximum = $product->is_sold_individually() ? 1 : $product->get_max_purchase_quantity();
        $item_class = (string) apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key);
        $remove_url = wc_get_cart_remove_url($cart_item_key);
        $remove_label = sprintf('Видалити %s з кошика', wp_strip_all_tags($product_name));
        $remove_link = sprintf(
            '<a href="%1$s" class="cart-item__remove remove" aria-label="%2$s" data-product_id="%3$d" data-product_sku="%4$s">%5$s Видалити</a>',
            esc_url($remove_url),
            esc_attr($remove_label),
            $product_id,
            esc_attr($product->get_sku()),
            $this->closeIcon()
        );

        echo '<article class="cart-item ' . esc_attr($item_class) . '" data-cart-item="' . esc_attr($cart_item_key) . '">';
        echo $permalink !== '' ? '<a class="cart-item__image" href="' . esc_url($permalink) . '">' : '<span class="cart-item__image">';
        echo wp_kses_post($thumbnail);

        if ($badge !== null) {
            echo '<span class="cart-item__badge maruderm-product-badge maruderm-product-badge--' . esc_attr($badge['tone']) . '">' . esc_html($badge['label']) . '</span>';
        }

        echo $permalink !== '' ? '</a>' : '</span>';
        echo '<div class="cart-item__details"><span class="cart-item__category">' . esc_html($category) . '</span><h3>';
        echo $permalink !== '' ? '<a href="' . esc_url($permalink) . '">' . wp_kses_post($product_name) . '</a>' : wp_kses_post($product_name);
        echo '</h3><div class="cart-item__meta">' . wp_kses_post(wc_get_formatted_cart_item_data($cart_item)) . '</div>';
        do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
        echo apply_filters('woocommerce_cart_item_remove_link', $remove_link, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div><div class="cart-item__actions"><strong>';
        echo apply_filters('woocommerce_cart_item_subtotal', $cart->get_product_subtotal($product, $quantity), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</strong><div class="cart-quantity" aria-label="Кількість товару ' . esc_attr(wp_strip_all_tags($product_name)) . '">';
        echo '<button type="button" data-cart-minus aria-label="Зменшити кількість">−</button>';
        echo '<input type="number" name="cart[' . esc_attr($cart_item_key) . '][qty]" min="1"' . ($maximum > 0 ? ' max="' . esc_attr((string) $maximum) . '"' : '') . ' step="1" value="' . esc_attr((string) $quantity) . '" data-cart-quantity aria-label="Кількість">';
        echo '<button type="button" data-cart-plus aria-label="Збільшити кількість">+</button></div></div></article>';
    }

    private function renderSummary(\WC_Cart $cart): void
    {
        $threshold = $this->freeShippingThreshold();
        $subtotal = (float) $cart->get_displayed_subtotal();
        $remaining = max(0.0, $threshold - $subtotal);
        $progress = $threshold > 0 ? min(100.0, ($subtotal / $threshold) * 100) : 100.0;
        $discount = (float) $cart->get_discount_total();

        echo '<aside class="order-summary" data-order-summary><span class="kicker">Підсумок</span><h2>Разом</h2>';
        echo '<div class="shipping-progress"><div class="shipping-progress__copy"><span>' . esc_html($remaining > 0 ? 'До безкоштовної доставки' : 'Безкоштовна доставка доступна') . '</span><strong>' . ($remaining > 0 ? wp_kses_post(wc_price($remaining)) : 'Готово') . '</strong></div>';
        echo '<div class="shipping-progress__track" role="progressbar" aria-label="Прогрес до безкоштовної доставки" aria-valuemin="0" aria-valuemax="' . esc_attr((string) $threshold) . '" aria-valuenow="' . esc_attr((string) $subtotal) . '"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<dl class="order-summary__totals"><div><dt>Товари</dt><dd>' . wp_kses_post($cart->get_cart_subtotal()) . '</dd></div>';

        foreach ($cart->get_coupons() as $code => $coupon) {
            $amount = $cart->get_coupon_discount_amount($code, $cart->display_prices_including_tax());
            echo '<div class="order-summary__discount"><dt>' . esc_html(wc_cart_totals_coupon_label($coupon, false)) . ' <a href="' . esc_url(wc_get_cart_remove_coupon_url($code)) . '" aria-label="Видалити промокод">×</a></dt><dd>−' . wp_kses_post(wc_price($amount)) . '</dd></div>';
        }

        if ($discount > 0 && $cart->get_coupons() === []) {
            echo '<div class="order-summary__discount"><dt>Знижка</dt><dd>−' . wp_kses_post(wc_price($discount)) . '</dd></div>';
        }

        foreach ($cart->get_fees() as $fee) {
            echo '<div><dt>' . esc_html($fee->name) . '</dt><dd>' . wp_kses_post(wc_price((float) $fee->total)) . '</dd></div>';
        }

        echo '<div><dt>Доставка</dt><dd>' . wp_kses_post($this->shippingLabel($cart)) . '</dd></div>';
        echo '<div class="order-summary__total"><dt>До сплати</dt><dd>' . wp_kses_post($cart->get_total()) . '</dd></div></dl>';
        $this->renderCoupon();
        echo '<a class="order-summary__checkout" href="' . esc_url(wc_get_checkout_url()) . '"><span>Перейти до оформлення</span>' . $this->arrowIcon() . '</a>';
        echo '<p class="order-summary__notice">Натискаючи кнопку, ти перейдеш до вибору доставки та способу оплати.</p>';
        echo '<div class="order-summary__trust"><article><span>01</span><strong>Оригінальна продукція</strong></article><article><span>02</span><strong>Безпечна оплата</strong></article></div></aside>';
    }

    private function renderCoupon(): void
    {
        if (!wc_coupons_enabled()) {
            return;
        }

        echo '<div class="promo-form"><label for="coupon_code">Промокод</label><div><input id="coupon_code" name="coupon_code" placeholder="Введи код" autocomplete="off"><button type="submit" name="apply_coupon" value="1">Застосувати</button></div></div>';
        do_action('woocommerce_cart_coupon');
    }

    private function renderHelp(): void
    {
        $contact_page = get_page_by_path('kontakty');
        $contact_url = $contact_page instanceof \WP_Post ? get_permalink($contact_page) : home_url('/kontakty/');

        echo '<aside class="cart-help"><div class="cart-help__mark">?</div><div><strong>Потрібна порада щодо догляду?</strong><p>Допоможемо перевірити сумісність формул і скласти послідовний ритуал.</p></div>';
        echo '<a href="' . esc_url($contact_url) . '">Написати нам ' . $this->arrowIcon() . '</a></aside>';
    }

    private function renderEmpty(): void
    {
        echo '<section class="cart-content"><div class="shell"><div class="cart-empty"><span class="cart-empty__art">' . $this->bagIcon() . '</span><span class="kicker">Почни свій ритуал</span><h2>У кошику поки порожньо</h2>';
        echo '<p>Знайди формули для шкіри, волосся й тіла — ми збережемо вибір тут.</p><a class="button button--dark" href="' . esc_url(wc_get_page_permalink('shop')) . '">Перейти до каталогу ' . $this->arrowIcon() . '</a></div></div></section>';
    }

    private function categoryLabel(int $product_id): string
    {
        $terms = get_the_terms($product_id, 'product_cat');

        if (!is_array($terms) || $terms === []) {
            return 'Maruderm';
        }

        usort($terms, static fn (\WP_Term $left, \WP_Term $right): int => $left->parent <=> $right->parent);

        return $terms[0]->name;
    }

    private function quantityLabel(int $quantity): string
    {
        $last_two = $quantity % 100;
        $last = $quantity % 10;
        $label = 'товарів';

        if ($last_two < 11 || $last_two > 14) {
            $label = $last === 1 ? 'товар' : ($last >= 2 && $last <= 4 ? 'товари' : 'товарів');
        }

        return sprintf('%d %s', $quantity, $label);
    }

    private function shippingLabel(\WC_Cart $cart): string
    {
        if (!$cart->needs_shipping()) {
            return 'Не потрібна';
        }

        if ($cart->show_shipping()) {
            $shipping_total = (float) $cart->get_shipping_total();

            return $shipping_total > 0 ? wc_price($shipping_total) : 'Безкоштовно';
        }

        return 'На оформленні';
    }

    private function freeShippingThreshold(): float
    {
        if (!class_exists('WC_Shipping_Zones') || !class_exists('WC_Shipping_Free_Shipping')) {
            return self::DEFAULT_FREE_SHIPPING_THRESHOLD;
        }

        $zones = \WC_Shipping_Zones::get_zones();
        $zones[] = ['shipping_methods' => \WC_Shipping_Zones::get_zone(0)->get_shipping_methods(true)];

        foreach ($zones as $zone) {
            foreach ($zone['shipping_methods'] ?? [] as $method) {
                if (!$method instanceof \WC_Shipping_Free_Shipping || $method->enabled !== 'yes') {
                    continue;
                }

                $minimum = (float) $method->get_option('min_amount', 0);

                if ($minimum > 0) {
                    return $minimum;
                }
            }
        }

        return self::DEFAULT_FREE_SHIPPING_THRESHOLD;
    }

    private function arrowIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    }

    private function closeIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"></path></svg>';
    }

    private function bagIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path></svg>';
    }
}
