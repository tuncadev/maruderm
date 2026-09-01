<?php
/**
 * Keeps HUTKO WooCommerce gateway settings in the project environment.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Hutko_Env_Config
{
    private const SETTINGS_OPTION = 'woocommerce_hutko_settings';

    /** @var array<string, list<string>> */
    private const SETTING_ENV_KEYS = [
        'enabled' => ['HUTKO_ENABLED'],
        'test_mode' => ['HUTKO_TEST_MODE'],
        'merchant_id' => ['HUTKO_MERCHANT_ID'],
        'secret_key' => ['HUTKO_SECRET_KEY', 'HUTKO_PAYMENT_KEY'],
        'integration_type' => ['HUTKO_INTEGRATION_TYPE'],
        'title' => ['HUTKO_TITLE'],
        'description' => ['HUTKO_DESCRIPTION'],
        'completed_order_status' => ['HUTKO_COMPLETED_ORDER_STATUS'],
        'expired_order_status' => ['HUTKO_EXPIRED_ORDER_STATUS'],
        'declined_order_status' => ['HUTKO_DECLINED_ORDER_STATUS'],
        'recurrent_payment' => ['HUTKO_RECURRENT_PAYMENT'],
    ];

    /** @var array<string, string>|null */
    private ?array $file_values = null;

    public function register(): void
    {
        add_filter('option_' . self::SETTINGS_OPTION, [$this, 'overlaySettings']);
        add_filter('default_option_' . self::SETTINGS_OPTION, [$this, 'overlaySettings']);
        add_filter('pre_update_option_' . self::SETTINGS_OPTION, [$this, 'removeManagedSettingsBeforeSave'], 10, 2);
        add_filter('woocommerce_available_payment_gateways', [$this, 'guardProductionAvailability'], 20);
    }

    /**
     * @param mixed $settings
     * @return array<string, mixed>
     */
    public function overlaySettings($settings): array
    {
        $settings = is_array($settings) ? $settings : [];

        foreach (self::SETTING_ENV_KEYS as $setting_key => $env_keys) {
            $found = false;
            $value = $this->settingEnvironmentValue($env_keys, $found);

            if (! $found) {
                continue;
            }

            $settings[$setting_key] = $this->normalizeSetting($setting_key, $value);
        }

        return $settings;
    }

    /**
     * Environment-owned values must not be copied into the WordPress database.
     *
     * @param mixed $new_settings
     * @param mixed $old_settings
     * @return array<string, mixed>
     */
    public function removeManagedSettingsBeforeSave($new_settings, $old_settings): array
    {
        $new_settings = is_array($new_settings) ? $new_settings : [];

        foreach (self::SETTING_ENV_KEYS as $setting_key => $env_keys) {
            $found = false;
            $this->settingEnvironmentValue($env_keys, $found);

            if ($found) {
                unset($new_settings[$setting_key]);
            }
        }

        return $new_settings;
    }

    /**
     * Hide a production-mode gateway until both merchant credentials exist.
     *
     * @param array<string, WC_Payment_Gateway> $gateways
     * @return array<string, WC_Payment_Gateway>
     */
    public function guardProductionAvailability(array $gateways): array
    {
        $gateway = $gateways['hutko'] ?? null;

        if (! $gateway instanceof WC_Payment_Gateway || ! empty($gateway->test_mode)) {
            return $gateways;
        }

        if ((int) ($gateway->merchant_id ?? 0) <= 0 || trim((string) ($gateway->secret_key ?? '')) === '') {
            unset($gateways['hutko']);
        }

        return $gateways;
    }

    private function environmentValue(string $key, bool &$found): string
    {
        $runtime_value = getenv($key);

        if ($runtime_value !== false) {
            $found = true;
            return (string) $runtime_value;
        }

        foreach ([$_ENV, $_SERVER] as $source) {
            if (array_key_exists($key, $source) && is_scalar($source[$key])) {
                $found = true;
                return (string) $source[$key];
            }
        }

        $file_values = $this->projectEnvValues();

        if (array_key_exists($key, $file_values)) {
            $found = true;
            return $file_values[$key];
        }

        $found = false;
        return '';
    }

    /**
     * Return the first configured environment alias for a gateway setting.
     *
     * @param list<string> $keys
     */
    private function settingEnvironmentValue(array $keys, bool &$found): string
    {
        foreach ($keys as $key) {
            $key_found = false;
            $value = $this->environmentValue($key, $key_found);

            if ($key_found) {
                $found = true;
                return $value;
            }
        }

        $found = false;
        return '';
    }

    /** @return array<string, string> */
    private function projectEnvValues(): array
    {
        if ($this->file_values !== null) {
            return $this->file_values;
        }

        $this->file_values = [];
        $env_path = dirname(__DIR__, 2) . '/.env';
        $lines = is_readable($env_path) ? file($env_path, FILE_IGNORE_NEW_LINES) : false;

        if (! is_array($lines)) {
            return $this->file_values;
        }

        foreach ($lines as $line) {
            if (! preg_match('/^\s*(HUTKO_[A-Z0-9_]+)\s*=\s*(.*)$/', $line, $matches)) {
                continue;
            }

            $this->file_values[$matches[1]] = $this->unquote(trim($matches[2]));
        }

        return $this->file_values;
    }

    private function unquote(string $value): string
    {
        $length = strlen($value);

        if ($length < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[$length - 1];

        return (($first === '"' && $last === '"') || ($first === "'" && $last === "'"))
            ? substr($value, 1, -1)
            : $value;
    }

    private function normalizeSetting(string $setting_key, string $value): string
    {
        if (in_array($setting_key, ['enabled', 'test_mode', 'recurrent_payment'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no';
        }

        if ($setting_key === 'merchant_id') {
            return preg_replace('/\D+/', '', trim($value)) ?: '';
        }

        if ($setting_key === 'integration_type') {
            return in_array($value, ['hosted', 'embedded'], true) ? $value : 'hosted';
        }

        if (str_ends_with($setting_key, '_order_status')) {
            return sanitize_key(str_replace('wc-', '', $value));
        }

        if ($setting_key === 'secret_key') {
            return trim($value);
        }

        return sanitize_text_field($value);
    }
}

(new Maruderm_Hutko_Env_Config())->register();
