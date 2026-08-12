<?php

declare(strict_types=1);

namespace Maruderm\Checkout;

if (!defined('ABSPATH')) {
    exit();
}

final class PaymentRenderer
{
    private const SESSION_KEY = 'maruderm_delivery';

    /** @var array<string, mixed> */
    private array $delivery;

    public function __construct()
    {
        $delivery = WC()->session?->get(self::SESSION_KEY, []);
        $this->delivery = is_array($delivery) ? $delivery : [];
    }

    public function render(): void
    {
        $cart = WC()->cart;

        if (!$cart instanceof \WC_Cart || $cart->is_empty()) {
            return;
        }

        $cart->calculate_shipping();
        $cart->calculate_totals();
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        WC()->payment_gateways()->set_current_gateway($gateways);

        echo '<main class="maruderm-payment" data-payment-page>';
        $this->renderHero();
        woocommerce_output_all_notices();
        echo '<section class="payment-content"><div class="shell payment-layout">';
        $this->renderForm($cart, $gateways);
        $this->renderSummary($cart);
        echo '</div></section>';
        echo '<div class="payment-plugin-shadow" aria-hidden="true"><div id="wcus-billing-fields"></div><div id="wcus-shipping-fields"></div></div>';
        echo '</main>';
    }

    private function renderHero(): void
    {
        echo '<section class="payment-hero checkout-hero"><div class="shell">';
        echo '<nav class="breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><a href="' . esc_url(wc_get_cart_url()) . '">Кошик</a><span>/</span><a href="' . esc_url(wc_get_checkout_url()) . '">Доставка</a><span>/</span><span>Оплата</span></nav>';
        echo '<div class="payment-hero__content"><div><span class="kicker">Останній крок до твого ритуалу</span><h1>Оплата</h1></div>';
        echo '<ol class="checkout-steps" aria-label="Етапи оформлення"><li class="is-complete"><span>✓</span><strong>Кошик</strong></li><li class="is-complete"><span>✓</span><strong>Доставка</strong></li><li class="is-active"><span>03</span><strong>Оплата</strong></li></ol>';
        echo '</div></div></section>';
    }

    /** @param array<string, \WC_Payment_Gateway> $gateways */
    private function renderForm(\WC_Cart $cart, array $gateways): void
    {
        $action = add_query_arg('step', 'payment', wc_get_checkout_url());

        echo '<form name="checkout" class="payment-form checkout woocommerce-checkout" method="post" action="' . esc_url($action) . '" enctype="multipart/form-data" data-payment-form novalidate>';
        echo '<input type="hidden" name="maruderm_checkout_step" value="payment">';
        $this->renderHiddenCheckoutData();
        echo '<section class="checkout-card"><div class="checkout-card__heading"><span>01</span><div><small>Спосіб оплати</small><h2>Як зручно оплатити?</h2></div></div>';
        $this->renderGateways($gateways);
        echo '</section>';
        $this->renderDeliveryReview();
        $this->renderTerms();
        echo '<div class="payment-form__actions"><a href="' . esc_url(wc_get_checkout_url()) . '"><span>←</span> Повернутися до доставки</a>';
        do_action('woocommerce_review_order_before_submit');
        echo '<button type="submit" name="woocommerce_checkout_place_order" id="place_order" value="Підтвердити замовлення" data-value="Підтвердити замовлення" data-payment-submit><span data-payment-submit-label>Підтвердити замовлення</span>' . $this->lockIcon() . '</button>';
        do_action('woocommerce_review_order_after_submit');
        wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce');
        echo '</div><p class="payment-form__status" data-payment-status aria-live="polite"></p></form>';
    }

    /** @param array<string, \WC_Payment_Gateway> $gateways */
    private function renderGateways(array $gateways): void
    {
        do_action('woocommerce_review_order_before_payment');
        echo '<div class="payment-methods wc_payment_methods payment_methods methods" role="radiogroup" aria-label="Спосіб оплати">';

        if ($gateways === []) {
            echo '<p class="payment-methods__empty">Наразі немає доступних способів оплати. Напиши нам — допоможемо завершити замовлення.</p>';
        }

        foreach ($gateways as $gateway) {
            $selected = (bool) $gateway->chosen;
            $presentation = $this->gatewayPresentation($gateway);

            echo '<label class="payment-method wc_payment_method payment_method_' . esc_attr($gateway->id) . ($selected ? ' is-selected' : '') . '" data-payment-method>';
            echo '<input id="payment_method_' . esc_attr($gateway->id) . '" class="input-radio" type="radio" name="payment_method" value="' . esc_attr($gateway->id) . '"' . checked($selected, true, false) . ' data-order_button_text="' . esc_attr($presentation['button']) . '">';
            echo '<span class="payment-method__radio"></span><span class="payment-method__icon ' . esc_attr($presentation['icon_class']) . '">' . $presentation['icon'] . '</span>';
            echo '<span class="payment-method__copy"><strong>' . esc_html($presentation['title']) . '</strong><small>' . esc_html($presentation['subtitle']) . '</small></span>';
            echo $presentation['mark']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</label>';
        }

        foreach ($gateways as $gateway) {
            $selected = (bool) $gateway->chosen;
            $presentation = $this->gatewayPresentation($gateway);

            echo '<div class="payment-panel payment_box payment_method_' . esc_attr($gateway->id) . ' ' . esc_attr($presentation['panel_class']) . '" data-payment-panel="' . esc_attr($gateway->id) . '"' . ($selected ? '' : ' style="display:none"') . '>';
            echo '<span class="payment-panel__shield">' . $presentation['panel_icon'] . '</span><div><strong>' . esc_html($presentation['panel_title']) . '</strong><p>' . esc_html($presentation['panel_text']) . '</p>';

            if ($gateway->has_fields()) {
                $gateway->payment_fields();
            }

            echo '</div></div>';
        }

        echo '</div>';
        do_action('woocommerce_review_order_after_payment');
    }

    private function renderDeliveryReview(): void
    {
        $name = trim((string) ($this->delivery['billing_first_name'] ?? '') . ' ' . (string) ($this->delivery['billing_last_name'] ?? ''));
        $contact = trim((string) ($this->delivery['billing_phone'] ?? '') . ' · ' . (string) ($this->delivery['billing_email'] ?? ''), ' ·');
        [$delivery_title, $delivery_address] = $this->deliveryDescription();

        echo '<section class="checkout-card"><div class="checkout-card__heading"><span>02</span><div><small>Перевірка даних</small><h2>Контакти й доставка</h2></div></div><div class="payment-review">';
        echo '<article><span>' . $this->userIcon() . '</span><div><small>Отримувач</small><strong>' . esc_html($name) . '</strong><p>' . esc_html($contact) . '</p></div><a href="' . esc_url(wc_get_checkout_url()) . '" aria-label="Редагувати контактні дані">Редагувати</a></article>';
        echo '<article><span class="payment-review__icon--green">' . $this->locationIcon() . '</span><div><small>Доставка</small><strong>' . esc_html($delivery_title) . '</strong><p>' . esc_html($delivery_address) . '</p></div><a href="' . esc_url(wc_get_checkout_url()) . '" aria-label="Редагувати доставку">Редагувати</a></article>';
        echo '</div></section>';
    }

    private function renderTerms(): void
    {
        $terms_url = wc_get_page_permalink('terms');
        $privacy_url = get_privacy_policy_url();

        echo '<div class="form-row validate-required payment-consent"><label><input id="terms" class="input-checkbox" type="checkbox" name="terms" required><span></span><p>Підтверджую замовлення та погоджуюся з ';
        echo '<a href="' . esc_url($terms_url !== '' ? $terms_url : '#') . '" target="_blank">публічною офертою</a> і <a href="' . esc_url($privacy_url !== '' ? $privacy_url : '#') . '" target="_blank">політикою конфіденційності</a>.</p></label>';
        echo '<input type="hidden" name="terms-field" value="1"></div>';
    }

    private function renderHiddenCheckoutData(): void
    {
        $standard_fields = [
            'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email', 'billing_country',
            'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode',
            'order_comments',
        ];

        foreach ($standard_fields as $field) {
            $value = $field === 'billing_country' ? 'UA' : (string) ($this->delivery[$field] ?? '');
            echo '<input id="' . esc_attr($field) . '" type="hidden" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '">';
        }

        foreach ($this->delivery as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'wcus_') || is_array($value)) {
                continue;
            }

            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr((string) $value) . '">';
        }

        if (!empty($this->delivery['shipping_type'])) {
            echo '<input type="hidden" name="shipping_type" value="' . esc_attr((string) $this->delivery['shipping_type']) . '">';
        }

        $shipping_methods = $this->delivery['shipping_method'] ?? WC()->session->get('chosen_shipping_methods', []);

        foreach (is_array($shipping_methods) ? $shipping_methods : [] as $index => $method) {
            echo '<input id="shipping_method_' . esc_attr((string) $index) . '" type="hidden" name="shipping_method[' . esc_attr((string) $index) . ']" value="' . esc_attr((string) $method) . '" data-index="' . esc_attr((string) $index) . '">';
        }

        echo '<input id="ship-to-different-address-checkbox" type="hidden" name="ship_to_different_address" value="0">';
    }

    private function renderSummary(\WC_Cart $cart): void
    {
        echo '<aside class="delivery-summary payment-summary"><div class="delivery-summary__heading"><div><span class="kicker">Фінальна перевірка</span><h2>' . esc_html($this->quantityLabel($cart->get_cart_contents_count())) . '</h2></div><a href="' . esc_url(wc_get_cart_url()) . '">Редагувати</a></div><div class="delivery-summary__items">';

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $this->renderSummaryItem($cart, $cart_item_key, $cart_item);
        }

        echo '</div><dl class="delivery-summary__totals"><div><dt>Товари</dt><dd>' . wp_kses_post($cart->get_cart_subtotal()) . '</dd></div><div><dt>Доставка</dt><dd>' . wp_kses_post($this->shippingTotal($cart)) . '</dd></div><div><dt>До сплати</dt><dd>' . wp_kses_post($cart->get_total()) . '</dd></div></dl>';
        echo '<div class="payment-summary__secure"><span>' . $this->shieldIcon() . '</span><div><strong>Безпечне оформлення</strong><small>Дані захищені під час передавання</small></div></div>';
        $contact_page = get_page_by_path('kontakty');
        $contact_url = $contact_page instanceof \WP_Post ? get_permalink($contact_page) : home_url('/kontakty/');
        echo '<div class="payment-summary__support"><strong>Є питання перед оплатою?</strong><a href="' . esc_url($contact_url) . '">Написати нам ' . $this->arrowIcon() . '</a></div></aside>';
    }

    /** @param array<string, mixed> $cart_item */
    private function renderSummaryItem(\WC_Cart $cart, string $cart_item_key, array $cart_item): void
    {
        $product = $cart_item['data'] ?? null;
        $quantity = (int) ($cart_item['quantity'] ?? 0);

        if (!$product instanceof \WC_Product || !$product->exists() || $quantity < 1) {
            return;
        }

        $product_id = (int) ($cart_item['product_id'] ?? $product->get_id());
        $terms = get_the_terms($product_id, 'product_cat');
        $category = is_array($terms) && $terms !== [] ? $terms[0]->name : 'Maruderm';
        $name = (string) apply_filters('woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key);

        echo '<article class="delivery-summary-item"><a href="' . esc_url($product->get_permalink($cart_item)) . '">' . wp_kses_post($product->get_image('woocommerce_thumbnail')) . '</a>';
        echo '<div><span>' . esc_html($category) . '</span><strong>' . wp_kses_post($name) . '</strong><small>' . esc_html((string) $quantity) . ' × ' . wp_kses_post(wc_price((float) $product->get_price())) . '</small></div><strong>' . wp_kses_post($cart->get_product_subtotal($product, $quantity)) . '</strong></article>';
    }

    /** @return array{title: string, subtitle: string, button: string, icon_class: string, icon: string, mark: string, panel_class: string, panel_icon: string, panel_title: string, panel_text: string} */
    private function gatewayPresentation(\WC_Payment_Gateway $gateway): array
    {
        if ($gateway->id === 'cod') {
            return ['title' => 'Під час отримання', 'subtitle' => 'Карткою або готівкою у відділенні', 'button' => 'Підтвердити замовлення', 'icon_class' => 'payment-method__icon--yellow', 'icon' => $this->walletIcon(), 'mark' => '<span class="payment-method__tag">Без комісії</span>', 'panel_class' => 'payment-panel--receipt', 'panel_icon' => $this->storeIcon(), 'panel_title' => 'Оплата при отриманні замовлення', 'panel_text' => 'Сплати замовлення карткою або готівкою після огляду у відділенні Нової пошти.'];
        }

        if ($gateway->id === 'bacs') {
            return ['title' => 'Переказ на банківський рахунок', 'subtitle' => 'Оплата за реквізитами компанії', 'button' => 'Перейти до реквізитів', 'icon_class' => 'payment-method__icon--blue', 'icon' => $this->bankIcon(), 'mark' => '<span class="payment-method__tag payment-method__tag--blue">IBAN</span>', 'panel_class' => 'payment-panel--bank', 'panel_icon' => $this->bankIcon(), 'panel_title' => 'Оплата за реквізитами після оформлення', 'panel_text' => 'Реквізити для переказу з’являться після оформлення. Замовлення буде зарезервоване до підтвердження оплати.'];
        }

        return ['title' => wp_strip_all_tags($gateway->get_title()), 'subtitle' => 'Безпечна оплата через платіжний сервіс', 'button' => $gateway->order_button_text ?: 'Сплатити замовлення', 'icon_class' => '', 'icon' => $this->cardIcon(), 'mark' => '<span class="payment-method__marks"><b>VISA</b><b>MC</b></span>', 'panel_class' => '', 'panel_icon' => $this->shieldIcon(), 'panel_title' => 'Захищена онлайн-оплата', 'panel_text' => wp_strip_all_tags($gateway->get_description()) ?: 'Після підтвердження WooCommerce перенаправить тебе до захищеного платіжного сервісу.'];
    }

    /** @return array{0: string, 1: string} */
    private function deliveryDescription(): array
    {
        $method = (string) (($this->delivery['shipping_method'][0] ?? ''));

        if (str_starts_with($method, 'nova_poshta_shipping')) {
            $courier = (int) ($this->delivery['wcus_np_billing_custom_address_active'] ?? 0) === 1;

            if ($courier) {
                $settlement = (string) ($this->delivery['wcus_np_billing_settlement_full'] ?? $this->delivery['wcus_np_billing_settlement_name'] ?? '');
                $street = (string) ($this->delivery['wcus_np_billing_street_full'] ?? $this->delivery['wcus_np_billing_street_name'] ?? '');
                $house = (string) ($this->delivery['wcus_np_billing_house'] ?? '');
                $flat = (string) ($this->delivery['wcus_np_billing_flat'] ?? '');

                return ['Нова пошта · Кур’єр', trim($settlement . ', ' . $street . ', ' . $house . ($flat !== '' ? ', кв. ' . $flat : ''), ' ,')];
            }

            $warehouse = (string) ($this->delivery['wcus_np_billing_warehouse_name'] ?? 'Відділення або поштомат');
            $city = (string) ($this->delivery['wcus_np_billing_city_name'] ?? '');

            return ['Нова пошта · ' . $warehouse, $city];
        }

        return ['Самовивіз', 'Деталі отримання підтвердить менеджер'];
    }

    private function shippingTotal(\WC_Cart $cart): string
    {
        $total = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();

        return $total > 0 ? wc_price($total) : 'Безкоштовно';
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

    private function svg(string $paths): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';
    }

    private function cardIcon(): string { return $this->svg('<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18M7 15h4"></path>'); }
    private function walletIcon(): string { return $this->svg('<path d="M4 6h14a2 2 0 0 1 2 2v10H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h12"></path><path d="M15 11h7v4h-7a2 2 0 0 1 0-4Z"></path>'); }
    private function bankIcon(): string { return $this->svg('<path d="m3 9 9-5 9 5M5 10v8M10 10v8M14 10v8M19 10v8M3 20h18"></path>'); }
    private function shieldIcon(): string { return $this->svg('<path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6l-7-3Z"></path><path d="m9 12 2 2 4-5"></path>'); }
    private function storeIcon(): string { return $this->svg('<path d="M4 10h16M6 10v10h12V10M5 4h14l1 6H4l1-6Z"></path>'); }
    private function userIcon(): string { return $this->svg('<circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>'); }
    private function locationIcon(): string { return $this->svg('<path d="M12 21s7-6 7-12a7 7 0 1 0-14 0c0 6 7 12 7 12Z"></path><circle cx="12" cy="9" r="2.5"></circle>'); }
    private function lockIcon(): string { return $this->svg('<rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>'); }
    private function arrowIcon(): string { return $this->svg('<path d="M5 12h14M13 6l6 6-6 6"></path>'); }
}
