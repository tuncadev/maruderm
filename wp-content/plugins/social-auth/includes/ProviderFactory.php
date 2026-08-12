<?php

namespace SocialAuth;

use InvalidArgumentException;
use SocialAuth\Contracts\ProviderInterface;
use SocialAuth\Providers\AppleProvider;
use SocialAuth\Providers\FacebookProvider;
use SocialAuth\Providers\GoogleProvider;

class ProviderFactory
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function make(string $provider): ProviderInterface
    {
        $provider = sanitize_key($provider);

        if (! $this->config->isProviderEnabled($provider)) {
            throw new InvalidArgumentException('Provider is disabled.');
        }

        $providerConfig = $this->config->getProviderConfig($provider);
        $requiredKeys = [
            'google' => ['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI'],
            'facebook' => ['FACEBOOK_CLIENT_ID', 'FACEBOOK_CLIENT_SECRET', 'FACEBOOK_REDIRECT_URI'],
            'apple' => ['APPLE_CLIENT_ID', 'APPLE_TEAM_ID', 'APPLE_KEY_ID', 'APPLE_PRIVATE_KEY', 'APPLE_REDIRECT_URI'],
        ];

        $map = [
            'google' => GoogleProvider::class,
            'facebook' => FacebookProvider::class,
            'apple' => AppleProvider::class,
        ];

        if (! isset($map[$provider])) {
            throw new InvalidArgumentException('Unsupported provider.');
        }

        foreach ($requiredKeys[$provider] as $key) {
            if (empty($providerConfig[$key])) {
                throw new InvalidArgumentException('Provider config is incomplete.');
            }
        }

        $class = $map[$provider];
        $instance = new $class($providerConfig);

        if (! $instance instanceof ProviderInterface) {
            throw new InvalidArgumentException('Provider implementation is invalid.');
        }

        return $instance;
    }
}
