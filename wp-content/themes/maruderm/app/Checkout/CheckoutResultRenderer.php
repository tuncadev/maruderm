<?php

declare(strict_types=1);

namespace Maruderm\Checkout;

if (!defined('ABSPATH')) {
    exit();
}

final class CheckoutResultRenderer
{
    private \WC_Order $order;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
    }

    public function renderBankTransfer(): void
    {
        $details = $this->bankDetails();
        $has_account = count($details) > 1;

        echo '<main class="maruderm-bank-transfer" data-bank-transfer-page>';
        echo '<section class="bank-transfer-hero"><div class="shell bank-transfer-hero__content"><nav class="breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><span>Реквізити</span></nav>';
        echo '<span class="bank-transfer-hero__icon">' . $this->bankIcon() . '</span><span class="kicker">Банківський переказ</span><h1>Реквізити для оплати</h1>';
        echo '<p>Замовлення <strong>' . esc_html($this->displayOrderNumber()) . '</strong> зарезервоване. ' . esc_html($has_account ? 'Перекажи точну суму за реквізитами нижче.' : 'Не переказуй кошти, доки не отримаєш підтверджені реквізити.') . '</p></div></section>';
        echo '<section class="bank-transfer-content"><div class="shell bank-transfer-layout"><div class="bank-details-card"><div class="bank-details-card__head"><div><span class="kicker">Дані компанії</span><h2>Платіжні реквізити</h2></div><span>' . esc_html($has_account ? 'Підтверджені WooCommerce' : 'Потребують налаштування') . '</span></div>';
        echo '<div class="bank-details">';

        foreach ($details as $detail) {
            echo '<div class="bank-detail"><span>' . esc_html($detail['label']) . '</span><strong>' . esc_html($detail['value']) . '</strong><button type="button" data-copy-value="' . esc_attr($detail['value']) . '" aria-label="Копіювати ' . esc_attr($detail['label']) . '">' . $this->copyIcon() . '<span>Копіювати</span></button></div>';
        }

        echo '</div><div class="bank-details-card__notice">' . $this->alertIcon() . '<p>';
        echo esc_html($has_account
            ? 'Перед переказом перевір суму й обов’язково вкажи номер замовлення у призначенні платежу.'
            : 'Банківські реквізити ще не внесені в WooCommerce. Не здійснюй переказ — підтримка надішле підтверджені дані окремо.');
        echo '</p></div></div>';
        echo '<aside class="bank-order-card"><span class="kicker">До сплати</span><strong>' . wp_kses_post($this->order->get_formatted_order_total()) . '</strong><dl><div><dt>Номер замовлення</dt><dd>' . esc_html($this->displayOrderNumber()) . '</dd></div><div><dt>Статус</dt><dd>' . esc_html($this->statusLabel()) . '</dd></div><div><dt>Резерв</dt><dd>До підтвердження</dd></div></dl>';
        $confirmation_label = $has_account ? 'Я здійснив переказ' : 'Перейти до замовлення';
        echo '<a class="bank-order-card__confirm" href="' . esc_url(add_query_arg('view', 'thanks', $this->order->get_checkout_order_received_url())) . '">' . esc_html($confirmation_label) . ' ' . $this->arrowIcon() . '</a>';
        echo '<a class="bank-order-card__back" href="' . esc_url($this->order->get_checkout_payment_url()) . '">← Обрати інший спосіб оплати</a><p>Зарахування банківського переказу може тривати до одного робочого дня.</p></aside></div></section></main>';
        $this->runWooCommerceThankYouHooks();
    }

    public function renderThankYou(): void
    {
        $catalog_url = wc_get_page_permalink('shop');
        $contact_page = get_page_by_path('kontakty');
        $contact_url = $contact_page instanceof \WP_Post ? get_permalink($contact_page) : home_url('/kontakty/');

        echo '<main class="maruderm-checkout-result"><section class="checkout-result checkout-result--thanks"><div class="shell checkout-result__shell"><div class="checkout-result__visual"><span class="checkout-result__orbit checkout-result__orbit--one"></span><span class="checkout-result__orbit checkout-result__orbit--two"></span><span class="checkout-result__icon">' . $this->bagIcon() . '</span><span class="checkout-result__note">' . esc_html($this->displayOrderNumber()) . '</span></div>';
        echo '<div class="checkout-result__content"><span class="kicker">Замовлення прийнято</span><h1>Дякуємо за замовлення!</h1><p>' . esc_html($this->thankYouDescription()) . '</p>';
        echo '<div class="checkout-result__meta"><div><span>Номер замовлення</span><strong>' . esc_html($this->displayOrderNumber()) . '</strong></div><div><span>Сума</span><strong>' . wp_kses_post($this->order->get_formatted_order_total()) . '</strong></div><div><span>Статус</span><strong>' . esc_html($this->statusLabel()) . '</strong></div></div>';
        echo '<div class="checkout-result__actions"><a class="button button--dark" href="' . esc_url($catalog_url) . '">Продовжити покупки ' . $this->arrowIcon() . '</a><a class="text-link" href="' . esc_url(home_url('/')) . '">На головну</a></div>';
        echo '<div class="checkout-result__help"><span>?</span><p>Потрібна допомога із замовленням?</p><a href="' . esc_url($contact_url) . '">Написати підтримці</a></div></div></div></section></main>';
        $this->runWooCommerceThankYouHooks();
    }

    /** @return array<int, array{label: string, value: string}> */
    private function bankDetails(): array
    {
        $details = [];
        $gateways = WC()->payment_gateways()->payment_gateways();
        $gateway = $gateways['bacs'] ?? null;
        $accounts = $gateway instanceof \WC_Gateway_BACS ? $gateway->account_details : [];
        $account = is_array($accounts) ? ($accounts[0] ?? []) : [];
        $labels = [
            'account_name' => 'Отримувач',
            'account_number' => 'Номер рахунку',
            'bank_name' => 'Банк',
            'sort_code' => 'МФО / код банку',
            'iban' => 'IBAN',
            'bic' => 'BIC / SWIFT',
        ];

        foreach ($labels as $key => $label) {
            $value = is_array($account) ? trim((string) ($account[$key] ?? '')) : '';

            if ($value !== '') {
                $details[] = ['label' => $label, 'value' => $value];
            }
        }

        $details[] = ['label' => 'Призначення', 'value' => 'Оплата замовлення ' . $this->displayOrderNumber()];

        return $details;
    }

    private function displayOrderNumber(): string
    {
        return 'MD-' . $this->order->get_order_number();
    }

    private function statusLabel(): string
    {
        if ($this->order->is_paid()) {
            return 'Оплачено';
        }

        if ($this->order->get_payment_method() === 'bacs') {
            return 'Очікує переказу';
        }

        if ($this->order->get_payment_method() === 'cod') {
            return 'Оплата при отриманні';
        }

        return wc_get_order_status_name($this->order->get_status());
    }

    private function thankYouDescription(): string
    {
        if ($this->order->get_payment_method() === 'bacs' && !$this->order->is_paid()) {
            return 'Ми перевіримо надходження переказу та повідомимо, коли замовлення буде підтверджене.';
        }

        return 'Ми надішлемо підтвердження на email і повідомимо, коли посилка вирушить.';
    }

    private function runWooCommerceThankYouHooks(): void
    {
        ob_start();
        do_action('woocommerce_before_thankyou', $this->order->get_id());
        do_action('woocommerce_thankyou_' . $this->order->get_payment_method(), $this->order->get_id());
        do_action('woocommerce_thankyou', $this->order->get_id());
        $integration_output = ob_get_clean();

        if (is_string($integration_output) && trim($integration_output) !== '') {
            echo '<div class="maruderm-checkout-integrations" hidden aria-hidden="true">' . $integration_output . '</div>';
        }
    }

    private function svg(string $paths): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';
    }

    private function bankIcon(): string { return $this->svg('<path d="m3 9 9-5 9 5M5 10v8M10 10v8M14 10v8M19 10v8M3 20h18"></path>'); }
    private function copyIcon(): string { return $this->svg('<rect x="8" y="8" width="11" height="11" rx="2"></rect><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"></path>'); }
    private function alertIcon(): string { return $this->svg('<path d="M12 4 3 20h18L12 4Z"></path><path d="M12 9v5M12 17h.01"></path>'); }
    private function arrowIcon(): string { return $this->svg('<path d="M5 12h14M13 6l6 6-6 6"></path>'); }
    private function bagIcon(): string { return $this->svg('<path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path>'); }
}
