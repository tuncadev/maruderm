<?php

namespace MarudermNovaPoshta\Api;

use MarudermNovaPoshta\Config;
use RuntimeException;

class TokenProvider
{
    private const TRANSIENT_KEY = 'maruderm_nova_poshta_jwt';

    public function __construct(private Config $config)
    {
    }

    public function getJwt(): string
    {
        $cached = get_transient(self::TRANSIENT_KEY);
        if (is_array($cached) && isset($cached['token'], $cached['expires_at'])) {
            $token = (string) $cached['token'];
            $expiresAt = (int) $cached['expires_at'];

            if ($token !== '' && $expiresAt > (time() + 60)) {
                return $token;
            }
        }

        return $this->refreshJwt();
    }

    public function refreshJwt(): string
    {
        $apiKey = $this->config->getApiKey();
        if ($apiKey === '') {
            throw new RuntimeException('NOVA_POCHTA_API_KEY is empty.');
        }

        $url = add_query_arg(
            ['apiKey' => $apiKey],
            rtrim($this->config->getBaseUrl(), '/') . '/clients/authorization'
        );

        $response = wp_remote_get($url, [
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            throw new RuntimeException('Failed to get Nova Poshta JWT: ' . $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($code >= 400 || ! is_array($body) || empty($body['jwt'])) {
            throw new RuntimeException('Nova Poshta JWT request failed with HTTP ' . $code . '.');
        }

        $jwt = (string) $body['jwt'];
        $ttl = 55 * MINUTE_IN_SECONDS;

        set_transient(self::TRANSIENT_KEY, [
            'token' => $jwt,
            'expires_at' => time() + $ttl,
        ], $ttl);

        return $jwt;
    }
}
