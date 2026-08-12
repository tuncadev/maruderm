<?php

namespace SocialAuth\Providers;

use RuntimeException;

class FacebookProvider extends AbstractProvider
{
    public function getName(): string
    {
        return 'facebook';
    }

    public function getAuthorizationUrl(string $state): string
    {
        return add_query_arg([
            'client_id' => $this->config['FACEBOOK_CLIENT_ID'],
            'redirect_uri' => $this->config['FACEBOOK_REDIRECT_URI'],
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state,
        ], 'https://www.facebook.com/v18.0/dialog/oauth');
    }

    public function fetchUserData(string $code): array
    {
        $token = $this->getJson(add_query_arg([
            'client_id' => $this->config['FACEBOOK_CLIENT_ID'],
            'client_secret' => $this->config['FACEBOOK_CLIENT_SECRET'],
            'redirect_uri' => $this->config['FACEBOOK_REDIRECT_URI'],
            'code' => $code,
        ], 'https://graph.facebook.com/v18.0/oauth/access_token'));

        if (empty($token['access_token'])) {
            throw new RuntimeException('Facebook token exchange failed.');
        }

        $profile = $this->getJson(add_query_arg([
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $token['access_token'],
        ], 'https://graph.facebook.com/me'));

        return [
            'email' => (string) ($profile['email'] ?? ''),
            'name' => (string) ($profile['name'] ?? ''),
            'provider' => 'facebook',
            'provider_id' => (string) ($profile['id'] ?? ''),
            'avatar' => (string) ($profile['picture']['data']['url'] ?? ''),
        ];
    }
}
