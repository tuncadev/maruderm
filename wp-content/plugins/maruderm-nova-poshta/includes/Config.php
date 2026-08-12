<?php

namespace MarudermNovaPoshta;

class Config
{
    public const OPTION_KEY = 'maruderm_nova_poshta_options';

    public function getApiKey(): string
    {
        return $this->get('NOVA_POCHTA_API_KEY');
    }

    public function getEnvironment(): string
    {
        $environment = strtolower($this->get('NOVA_POCHTA_ENVIRONMENT', 'stage'));
        return in_array($environment, ['stage', 'production'], true) ? $environment : 'stage';
    }

    public function getBaseUrl(): string
    {
        return $this->getEnvironment() === 'production'
            ? 'https://api.novapost.com/v.1.0'
            : 'https://api-stage.novapost.pl/v.1.0';
    }

    public function getCountryCode(): string
    {
        return 'UA';
    }

    public function getSenderName(): string
    {
        return $this->get('NOVA_POCHTA_SENDER_NAME');
    }

    public function getSenderPhone(): string
    {
        return $this->get('NOVA_POCHTA_SENDER_PHONE');
    }

    public function getSenderDivisionId(): int
    {
        return (int) $this->get('NOVA_POCHTA_SENDER_DIVISION_ID', '0');
    }

    public function getDefaultParcelWidth(): int
    {
        return (int) $this->get('NOVA_POCHTA_DEFAULT_PARCEL_WIDTH', '20');
    }

    public function getDefaultParcelLength(): int
    {
        return (int) $this->get('NOVA_POCHTA_DEFAULT_PARCEL_LENGTH', '30');
    }

    public function getDefaultParcelHeight(): int
    {
        return (int) $this->get('NOVA_POCHTA_DEFAULT_PARCEL_HEIGHT', '15');
    }

    public function getWebhookSecretToken(): string
    {
        return $this->get('NOVA_POCHTA_WEBHOOK_SECRET_TOKEN');
    }

    public function getWebhookSecretHeaderName(): string
    {
        return $this->get('NOVA_POCHTA_WEBHOOK_SECRET_HEADER', 'X-Nova-Poshta-Token');
    }

    public function getWebhookUrl(): string
    {
        $fromEnv = $this->get('NOVA_POCHTA_WEBHOOK_URL');
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        return rest_url('maruderm-nova-poshta/v1/webhook/tracking');
    }

    public function get(string $key, string $default = ''): string
    {
        $fromEnv = Env::get($key, '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $optionKey = strtolower($key);
        $options = get_option(self::OPTION_KEY, []);
        if (is_array($options) && isset($options[$optionKey]) && is_string($options[$optionKey])) {
            $value = trim($options[$optionKey]);
            if ($value !== '') {
                return $value;
            }
        }

        return $default;
    }
}
