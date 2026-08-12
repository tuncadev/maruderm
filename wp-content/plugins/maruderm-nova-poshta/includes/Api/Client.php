<?php

namespace MarudermNovaPoshta\Api;

use MarudermNovaPoshta\Config;
use RuntimeException;

class Client
{
    public function __construct(
        private Config $config,
        private TokenProvider $tokenProvider
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, [
            'payload' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function put(string $path, array $payload = []): array
    {
        return $this->request('PUT', $path, [
            'payload' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function delete(string $path, array $payload = []): array
    {
        return $this->request('DELETE', $path, [
            'payload' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = [], bool $retried = false): array
    {
        $url = rtrim($this->config->getBaseUrl(), '/') . '/' . ltrim($path, '/');

        $query = $options['query'] ?? [];
        if (is_array($query) && $query !== []) {
            $url = add_query_arg($query, $url);
        }

        $headers = [
            'Authorization' => $this->tokenProvider->getJwt(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Accept-language' => 'uk',
        ];

        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];

        if (isset($options['payload']) && is_array($options['payload']) && $options['payload'] !== []) {
            $args['body'] = wp_json_encode($options['payload']);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            throw new RuntimeException('Nova Poshta request failed: ' . $response->get_error_message());
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $rawBody = (string) wp_remote_retrieve_body($response);
        $body = json_decode($rawBody, true);
        $decodedBody = is_array($body) ? $body : ['raw' => $rawBody];

        if ($statusCode === 401 && ! $retried) {
            $this->tokenProvider->refreshJwt();
            return $this->request($method, $path, $options, true);
        }

        if ($statusCode >= 400) {
            $message = 'Nova Poshta API HTTP ' . $statusCode;
            if (isset($decodedBody['errors']) && is_array($decodedBody['errors']) && $decodedBody['errors'] !== []) {
                $message .= ': ' . implode('; ', array_map('strval', $decodedBody['errors']));
            }

            throw new RuntimeException($message);
        }

        return $decodedBody;
    }
}
