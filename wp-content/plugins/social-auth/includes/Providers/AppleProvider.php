<?php

namespace SocialAuth\Providers;

use RuntimeException;

class AppleProvider extends AbstractProvider
{
    public function getName(): string
    {
        return 'apple';
    }

    public function getAuthorizationUrl(string $state): string
    {
        return add_query_arg([
            'response_type' => 'code',
            'response_mode' => 'form_post',
            'client_id' => $this->config['APPLE_CLIENT_ID'],
            'redirect_uri' => $this->config['APPLE_REDIRECT_URI'],
            'scope' => 'name email',
            'state' => $state,
        ], 'https://appleid.apple.com/auth/authorize');
    }

    public function fetchUserData(string $code): array
    {
        $clientSecret = $this->buildClientSecret();

        $token = $this->postForm('https://appleid.apple.com/auth/token', [
            'client_id' => $this->config['APPLE_CLIENT_ID'],
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->config['APPLE_REDIRECT_URI'],
        ]);

        if (empty($token['id_token'])) {
            throw new RuntimeException('Apple token exchange failed.');
        }

        $claims = $this->decodeJwtPayload((string) $token['id_token']);

        return [
            'email' => (string) ($claims['email'] ?? ''),
            'name' => (string) ($claims['name'] ?? ''),
            'provider' => 'apple',
            'provider_id' => (string) ($claims['sub'] ?? ''),
            'avatar' => '',
        ];
    }

    private function buildClientSecret(): string
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $this->config['APPLE_KEY_ID'],
            'typ' => 'JWT',
        ];

        $issuedAt = time();
        $claims = [
            'iss' => $this->config['APPLE_TEAM_ID'],
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
            'aud' => 'https://appleid.apple.com',
            'sub' => $this->config['APPLE_CLIENT_ID'],
        ];

        $signingInput = $this->base64UrlEncode((string) wp_json_encode($header))
            . '.'
            . $this->base64UrlEncode((string) wp_json_encode($claims));

        $privateKey = openssl_pkey_get_private($this->config['APPLE_PRIVATE_KEY']);
        if (! $privateKey) {
            throw new RuntimeException('Invalid Apple private key.');
        }

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        if (! $ok || $signature === '') {
            throw new RuntimeException('Unable to sign Apple client secret.');
        }

        $joseSignature = $this->derToJose($signature, 64);

        return $signingInput . '.' . $this->base64UrlEncode($joseSignature);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid id_token format.');
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid id_token payload.');
        }

        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }

    private function derToJose(string $der, int $partLength): string
    {
        $offset = 3;
        $rLength = ord($der[$offset]);
        $offset++;
        $r = substr($der, $offset, $rLength);
        $offset += $rLength + 1;
        $sLength = ord($der[$offset]);
        $offset++;
        $s = substr($der, $offset, $sLength);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        return str_pad($r, $partLength / 2, "\x00", STR_PAD_LEFT)
            . str_pad($s, $partLength / 2, "\x00", STR_PAD_LEFT);
    }
}
