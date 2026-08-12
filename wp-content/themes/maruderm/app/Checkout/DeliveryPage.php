<?php

declare(strict_types=1);

namespace Maruderm\Checkout;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class DeliveryPage implements Registrable
{
    use Loadable;

    private const SESSION_KEY = 'maruderm_delivery';

    public function register(): void
    {
        add_filter('template_include', [$this, 'resolveTemplate'], 50);
        add_action('template_redirect', [$this, 'redirectEmptyCart'], 20);
        add_action('wp', [$this, 'removeInheritedHeader'], 20);
        add_action('wp_enqueue_scripts', [$this, 'disableNativeCheckoutUpdatesOnDelivery'], 100);
        add_action('wp_ajax_maruderm_save_delivery', [$this, 'saveDelivery']);
        add_action('wp_ajax_nopriv_maruderm_save_delivery', [$this, 'saveDelivery']);
        add_filter('woocommerce_checkout_get_value', [$this, 'restoreCheckoutValue'], 10, 2);
        add_filter('woocommerce_checkout_fields', [$this, 'relaxPaymentAddressFields']);
    }

    public function resolveTemplate(string $template): string
    {
        if (!$this->isCustomCheckoutStep()) {
            return $template;
        }

        $template_name = $this->isPaymentStep() ? 'woocommerce/payment-page.php' : 'woocommerce/delivery-page.php';
        $checkout_template = get_theme_file_path($template_name);

        return file_exists($checkout_template) ? $checkout_template : $template;
    }

    public function redirectEmptyCart(): void
    {
        if (!$this->isCustomCheckoutStep() || !function_exists('WC') || !WC()->cart instanceof \WC_Cart) {
            return;
        }

        if (WC()->cart->is_empty() && !is_customize_preview()) {
            wp_safe_redirect(wc_get_cart_url());
            exit();
        }

        if ($this->isPaymentStep() && !is_array(WC()->session->get(self::SESSION_KEY))) {
            wp_safe_redirect(remove_query_arg('step', wc_get_checkout_url()));
            exit();
        }
    }

    public function removeInheritedHeader(): void
    {
        if (!$this->isCustomCheckoutStep()) {
            return;
        }

        remove_action('martfury_after_header', 'martfury_page_header');
        remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
        remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
    }

    public function disableNativeCheckoutUpdatesOnDelivery(): void
    {
        if ($this->isDeliveryStep()) {
            wp_dequeue_script('wc-checkout');
        }
    }

    public function restoreCheckoutValue(mixed $value, string $input): mixed
    {
        if (!function_exists('WC') || !WC()->session) {
            return $value;
        }

        $delivery = WC()->session->get(self::SESSION_KEY, []);

        return is_array($delivery) && array_key_exists($input, $delivery) ? $delivery[$input] : $value;
    }

    public function relaxPaymentAddressFields(array $fields): array
    {
        $posted_step = isset($_POST['maruderm_checkout_step'])
            ? sanitize_key(wp_unslash($_POST['maruderm_checkout_step']))
            : '';

        if (!$this->isPaymentStep() && $posted_step !== 'payment') {
            return $fields;
        }

        foreach (['billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode'] as $field) {
            if (isset($fields['billing'][$field])) {
                $fields['billing'][$field]['required'] = false;
            }
        }

        return $fields;
    }

    public function saveDelivery(): void
    {
        if (!check_ajax_referer('maruderm-save-delivery', 'nonce', false)) {
            wp_send_json_error(['message' => 'Сесію форми завершено. Онови сторінку та спробуй ще раз.'], 403);
        }

        if (!function_exists('WC') || !WC()->cart instanceof \WC_Cart || WC()->cart->is_empty()) {
            wp_send_json_error(['message' => 'Кошик порожній. Додай товари перед оформленням.'], 400);
        }

        $data = $this->sanitizeRequest(wp_unslash($_POST));
        $errors = $this->validateContact($data);
        $selected_rate = $this->resolveSelectedRate($data['shipping_method'][0] ?? '');

        if (!$selected_rate instanceof \WC_Shipping_Rate) {
            $errors['shipping_method'] = 'Обери доступний спосіб доставки.';
        } elseif ($selected_rate->get_method_id() === 'nova_poshta_shipping') {
            $errors += $this->validateNovaPoshta($data);
        }

        if ($errors !== []) {
            wp_send_json_error([
                'message' => 'Перевір обов’язкові поля перед переходом до оплати.',
                'fields' => $errors,
            ], 422);
        }

        $chosen_methods = [$selected_rate->get_id()];
        WC()->session->set('chosen_shipping_methods', $chosen_methods);
        WC()->session->set(self::SESSION_KEY, $data);

        $customer = WC()->customer;

        if ($customer instanceof \WC_Customer) {
            $customer->set_billing_first_name($data['billing_first_name']);
            $customer->set_billing_last_name($data['billing_last_name']);
            $customer->set_billing_phone($data['billing_phone']);
            $customer->set_billing_email($data['billing_email']);
            $customer->set_billing_country('UA');
            $customer->save();
        }

        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        wp_send_json_success([
            'message' => 'Дані доставки збережено. Переходимо до оплати…',
            'redirect' => add_query_arg('step', 'payment', wc_get_checkout_url()),
        ]);
    }

    private function isDeliveryStep(): bool
    {
        return $this->isCustomCheckoutStep() && !$this->isPaymentStep();
    }

    private function isPaymentStep(): bool
    {
        return $this->isCustomCheckoutStep() && $this->currentStep() === 'payment';
    }

    private function isCustomCheckoutStep(): bool
    {
        return function_exists('is_checkout') && is_checkout() && !is_order_received_page();
    }

    private function currentStep(): string
    {
        return isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : 'delivery';
    }

    /** @param array<string, mixed> $request */
    private function sanitizeRequest(array $request): array
    {
        $data = [];
        $text_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_phone',
            'billing_email',
            'billing_country',
            'order_comments',
            'shipping_type',
        ];

        foreach ($text_fields as $field) {
            $data[$field] = sanitize_text_field((string) ($request[$field] ?? ''));
        }

        $data['billing_email'] = sanitize_email($data['billing_email']);
        $data['billing_country'] = 'UA';
        $data['shipping_method'] = array_values(array_map(
            'sanitize_text_field',
            is_array($request['shipping_method'] ?? null) ? $request['shipping_method'] : []
        ));

        foreach ($request as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'wcus_') || is_array($value)) {
                continue;
            }

            $data[sanitize_key($key)] = sanitize_text_field((string) $value);
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function validateContact(array $data): array
    {
        $errors = [];

        foreach (['billing_first_name', 'billing_last_name'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = 'Заповни це поле.';
            }
        }

        $phone_digits = preg_replace('/\D+/', '', (string) ($data['billing_phone'] ?? ''));

        if (!is_string($phone_digits) || strlen($phone_digits) < 10) {
            $errors['billing_phone'] = 'Введи коректний номер телефону.';
        }

        if (!is_email((string) ($data['billing_email'] ?? ''))) {
            $errors['billing_email'] = 'Введи коректну email-адресу.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    private function validateNovaPoshta(array $data): array
    {
        $is_courier = (int) ($data['wcus_np_billing_custom_address_active'] ?? 0) === 1;

        if ($is_courier) {
            foreach (['wcus_np_billing_settlement_name', 'wcus_np_billing_street_name', 'wcus_np_billing_house'] as $field) {
                if (trim((string) ($data[$field] ?? '')) === '') {
                    return ['nova_poshta' => 'Вкажи населений пункт, вулицю та номер будинку.'];
                }
            }

            return [];
        }

        if (empty($data['wcus_np_billing_city']) || empty($data['wcus_np_billing_warehouse'])) {
            return ['nova_poshta' => 'Обери населений пункт і відділення або поштомат.'];
        }

        return [];
    }

    private function resolveSelectedRate(string $rate_id): ?\WC_Shipping_Rate
    {
        WC()->cart->calculate_shipping();

        foreach (WC()->shipping()->get_packages() as $package) {
            foreach ($package['rates'] ?? [] as $rate) {
                if ($rate instanceof \WC_Shipping_Rate && hash_equals($rate->get_id(), $rate_id)) {
                    return $rate;
                }
            }
        }

        return null;
    }
}
