<?php

declare(strict_types=1);

namespace Maruderm\Checkout;

if (!defined('ABSPATH')) {
    exit();
}

final class DeliveryRenderer
{
    public function render(): void
    {
        $cart = WC()->cart;

        if (!$cart instanceof \WC_Cart || $cart->is_empty()) {
            return;
        }

        $cart->calculate_shipping();
        $cart->calculate_totals();
        $checkout = WC()->checkout();

        echo '<main class="maruderm-delivery" data-delivery-page>';
        $this->renderHero();
        woocommerce_output_all_notices();
        echo '<section class="delivery-content"><div class="shell delivery-layout">';
        $this->renderForm($checkout);
        $this->renderSummary($cart);
        echo '</div></section></main>';
    }

    private function renderHero(): void
    {
        echo '<section class="delivery-hero checkout-hero"><div class="shell">';
        echo '<nav class="breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><a href="' . esc_url(wc_get_cart_url()) . '">Кошик</a><span>/</span><span>Доставка</span></nav>';
        echo '<div class="delivery-hero__content"><div><span class="kicker">Куди доставити твої формули</span><h1>Доставка</h1></div>';
        echo '<ol class="checkout-steps" aria-label="Етапи оформлення"><li class="is-complete"><span>✓</span><strong>Кошик</strong></li><li class="is-active"><span>02</span><strong>Доставка</strong></li><li><span>03</span><strong>Оплата</strong></li></ol>';
        echo '</div></div></section>';
    }

    private function renderForm(\WC_Checkout $checkout): void
    {
        echo '<form class="delivery-form checkout woocommerce-checkout" method="post" data-delivery-form data-ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '" novalidate>';
        echo '<input type="hidden" name="action" value="maruderm_save_delivery"><input type="hidden" name="nonce" value="' . esc_attr(wp_create_nonce('maruderm-save-delivery')) . '">';
        echo '<input type="hidden" name="billing_country" value="UA">';
        $this->renderContactFields($checkout);
        $this->renderDeliveryMethods();
        $this->renderNote($checkout);
        echo '<div class="delivery-form__actions"><a href="' . esc_url(wc_get_cart_url()) . '"><span>←</span> Повернутися до кошика</a>';
        echo '<button type="button" data-delivery-submit><span>Продовжити до оплати</span>' . $this->arrowIcon() . '</button></div>';
        echo '<p class="delivery-form__status" data-delivery-status aria-live="polite"></p>';
        echo '<div class="delivery-plugin-shadow" aria-hidden="true"><div id="wcus-shipping-fields"></div></div>';
        echo '</form>';
    }

    private function renderContactFields(\WC_Checkout $checkout): void
    {
        echo '<section class="checkout-card"><div class="checkout-card__heading"><span>01</span><div><small>Контактні дані</small><h2>Хто отримує замовлення?</h2></div></div>';
        echo '<div class="delivery-fields delivery-fields--contact">';
        $this->renderField('billing_first_name', 'Ім’я *', 'given-name', 'Олена', (string) $checkout->get_value('billing_first_name'));
        $this->renderField('billing_last_name', 'Прізвище *', 'family-name', 'Коваль', (string) $checkout->get_value('billing_last_name'));
        $this->renderField('billing_phone', 'Телефон *', 'tel', '+380 67 000 00 00', (string) $checkout->get_value('billing_phone'), 'tel');
        $this->renderField('billing_email', 'Email *', 'email', 'name@email.com', (string) $checkout->get_value('billing_email'), 'email');
        echo '</div></section>';
    }

    private function renderField(string $name, string $label, string $autocomplete, string $placeholder, string $value, string $type = 'text'): void
    {
        echo '<label class="delivery-field" data-field="' . esc_attr($name) . '"><span>' . esc_html($label) . '</span>';
        echo '<input name="' . esc_attr($name) . '" type="' . esc_attr($type) . '" autocomplete="' . esc_attr($autocomplete) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($value) . '" required>';
        echo '<small data-field-error></small></label>';
    }

    private function renderDeliveryMethods(): void
    {
        $packages = WC()->shipping()->get_packages();
        $chosen = WC()->session->get('chosen_shipping_methods', []);

        echo '<section class="checkout-card"><div class="checkout-card__heading"><span>02</span><div><small>Спосіб отримання</small><h2>Як доставити замовлення?</h2></div></div>';
        echo '<div class="delivery-methods" role="radiogroup" aria-label="Спосіб доставки">';

        foreach ($packages as $package_index => $package) {
            $rates = $package['rates'] ?? [];
            $selected_id = isset($chosen[$package_index]) ? (string) $chosen[$package_index] : (string) array_key_first($rates);

            foreach ($rates as $rate) {
                if (!$rate instanceof \WC_Shipping_Rate) {
                    continue;
                }

                $selected = $selected_id === $rate->get_id();
                $nova_poshta = $rate->get_method_id() === 'nova_poshta_shipping';
                $display_label = $this->shippingRateLabel($rate);
                $description = $nova_poshta ? 'Нова пошта · 1–3 робочі дні' : 'Забери замовлення у зручний час';

                echo '<label class="delivery-method' . ($selected ? ' is-selected' : '') . '" data-shipping-method data-nova-poshta="' . ($nova_poshta ? 'true' : 'false') . '">';
                echo '<input class="shipping_method" type="radio" name="shipping_method[' . esc_attr((string) $package_index) . ']" value="' . esc_attr($rate->get_id()) . '"' . checked($selected, true, false) . ' data-method-label="' . esc_attr($display_label) . '">';
                echo '<span class="delivery-method__radio"></span><span class="delivery-method__icon' . ($nova_poshta ? '' : ' delivery-method__icon--pink') . '">' . ($nova_poshta ? $this->storeIcon() : $this->truckIcon()) . '</span>';
                echo '<span class="delivery-method__copy"><strong>' . esc_html($display_label) . '</strong><small>' . esc_html($description) . '</small></span>';
                echo '<strong>' . wp_kses_post($this->shippingRatePrice($rate)) . '</strong></label>';
            }
        }

        echo '</div><div class="delivery-panel" data-delivery-panel><div id="wcus-billing-fields"></div>';
        echo '<p class="delivery-panel__hint">' . $this->locationIcon() . '<span data-delivery-hint>Обери населений пункт і відділення або поштомат Нової пошти.</span></p></div></section>';
    }

    private function renderNote(\WC_Checkout $checkout): void
    {
        echo '<section class="checkout-card checkout-card--note"><div class="checkout-card__heading"><span>03</span><div><small>За бажанням</small><h2>Коментар до замовлення</h2></div></div>';
        echo '<label class="delivery-field delivery-field--wide"><span class="screen-reader-text">Коментар до замовлення</span><textarea name="order_comments" rows="4" placeholder="Наприклад, бажаний час доставки">' . esc_textarea((string) $checkout->get_value('order_comments')) . '</textarea></label></section>';
    }

    private function renderSummary(\WC_Cart $cart): void
    {
        echo '<aside class="delivery-summary"><div class="delivery-summary__heading"><div><span class="kicker">Твоє замовлення</span><h2>' . esc_html($this->quantityLabel($cart->get_cart_contents_count())) . '</h2></div><a href="' . esc_url(wc_get_cart_url()) . '">Редагувати</a></div>';
        echo '<div class="delivery-summary__items">';

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $this->renderSummaryItem($cart, $cart_item_key, $cart_item);
        }

        echo '</div><dl class="delivery-summary__totals"><div><dt>Товари</dt><dd>' . wp_kses_post($cart->get_cart_subtotal()) . '</dd></div>';
        echo '<div><dt>Доставка</dt><dd>' . wp_kses_post($this->shippingTotal($cart)) . '</dd></div>';
        echo '<div><dt>До сплати</dt><dd>' . wp_kses_post($cart->get_total()) . '</dd></div></dl>';
        echo '<div class="delivery-summary__promise"><span>' . $this->locationIcon() . '</span><div><strong data-delivery-method-label>' . esc_html($this->chosenShippingLabel()) . '</strong><small>Орієнтовно 1–3 робочі дні</small></div></div>';
        echo '<p>Оплата буде обрана на наступному кроці.</p></aside>';
    }

    /** @param array<string, mixed> $cart_item */
    private function renderSummaryItem(\WC_Cart $cart, string $cart_item_key, array $cart_item): void
    {
        $product = $cart_item['data'] ?? null;
        $quantity = (int) ($cart_item['quantity'] ?? 0);

        if (!$product instanceof \WC_Product || !$product->exists() || $quantity < 1) {
            return;
        }

        $permalink = $product->is_visible() ? $product->get_permalink($cart_item) : '';
        $product_id = (int) ($cart_item['product_id'] ?? $product->get_id());
        $name = (string) apply_filters('woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key);

        echo '<article class="delivery-summary-item"><a href="' . esc_url($permalink) . '">' . wp_kses_post($product->get_image('woocommerce_thumbnail')) . '</a>';
        echo '<div><span>' . esc_html($this->categoryLabel($product_id)) . '</span><strong>' . wp_kses_post($name) . '</strong><small>' . esc_html((string) $quantity) . ' × ' . wp_kses_post(wc_price((float) $product->get_price())) . '</small></div>';
        echo '<strong>' . wp_kses_post($cart->get_product_subtotal($product, $quantity)) . '</strong></article>';
    }

    private function shippingRatePrice(\WC_Shipping_Rate $rate): string
    {
        $cost = (float) $rate->get_cost() + array_sum(array_map('floatval', $rate->get_taxes()));

        return $cost > 0 ? wc_price($cost) : 'Безкоштовно';
    }

    private function shippingTotal(\WC_Cart $cart): string
    {
        $total = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();

        return $total > 0 ? wc_price($total) : 'Безкоштовно';
    }

    private function chosenShippingLabel(): string
    {
        $chosen = WC()->session->get('chosen_shipping_methods', []);

        foreach (WC()->shipping()->get_packages() as $index => $package) {
            $chosen_id = $chosen[$index] ?? '';
            $rate = $package['rates'][$chosen_id] ?? null;

            if ($rate instanceof \WC_Shipping_Rate) {
                return $this->shippingRateLabel($rate);
            }
        }

        return 'Спосіб доставки';
    }

    private function shippingRateLabel(\WC_Shipping_Rate $rate): string
    {
        if ($rate->get_method_id() === 'nova_poshta_shipping') {
            return 'Відділення або поштомат';
        }

        if ($rate->get_method_id() === 'local_pickup') {
            return 'Самовивіз';
        }

        return $rate->get_label();
    }

    private function categoryLabel(int $product_id): string
    {
        $terms = get_the_terms($product_id, 'product_cat');

        return is_array($terms) && $terms !== [] ? $terms[0]->name : 'Maruderm';
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

    private function arrowIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    }

    private function storeIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16M6 10v10h12V10M5 4h14l1 6H4l1-6Z"></path><path d="M9 20v-5h6v5"></path></svg>';
    }

    private function truckIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"></path><circle cx="7" cy="18" r="2"></circle><circle cx="18" cy="18" r="2"></circle></svg>';
    }

    private function locationIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6 7-12a7 7 0 1 0-14 0c0 6 7 12 7 12Z"></path><circle cx="12" cy="9" r="2.5"></circle></svg>';
    }
}
