<?php

namespace SocialAuth\Providers;

use RuntimeException;
use SocialAuth\Contracts\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var array<string, string>
     */
    protected array $config;

    /**
     * @param array<string, string> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string, string> $payload
     * @return array<string, mixed>
     */
    protected function postForm(string $url, array $payload): array
    {
        $response = wp_remote_post($url, [
            'timeout' => 20,
            'body' => $payload,
        ]);

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status < 200 || $status >= 300 || ! is_array($decoded)) {
            throw new RuntimeException('OAuth provider request failed.');
        }

        return $decoded;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    protected function getJson(string $url, array $headers = []): array
    {
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status < 200 || $status >= 300 || ! is_array($decoded)) {
            throw new RuntimeException('OAuth provider request failed.');
        }

        return $decoded;
    }
}
