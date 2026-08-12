<?php

namespace SocialAuth\Providers;

use RuntimeException;

class GoogleProvider extends AbstractProvider
{
    public function getName(): string
    {
        return 'google';
    }

    public function getAuthorizationUrl(string $state): string
    {
        return add_query_arg([
            'client_id' => $this->config['GOOGLE_CLIENT_ID'],
            'redirect_uri' => $this->config['GOOGLE_REDIRECT_URI'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ], 'https://accounts.google.com/o/oauth2/v2/auth');
    }

    public function fetchUserData(string $code): array
    {
        $token = $this->postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->config['GOOGLE_CLIENT_ID'],
            'client_secret' => $this->config['GOOGLE_CLIENT_SECRET'],
            'redirect_uri' => $this->config['GOOGLE_REDIRECT_URI'],
            'grant_type' => 'authorization_code',
        ]);

        if (empty($token['access_token'])) {
            throw new RuntimeException('Google token exchange failed.');
        }

        $profile = $this->getJson('https://www.googleapis.com/oauth2/v2/userinfo', [
            'Authorization' => 'Bearer ' . $token['access_token'],
        ]);

        return [
            'email' => (string) ($profile['email'] ?? ''),
            'name' => (string) ($profile['name'] ?? ''),
            'provider' => 'google',
            'provider_id' => (string) ($profile['id'] ?? ''),
            'avatar' => (string) ($profile['picture'] ?? ''),
        ];
    }
}
