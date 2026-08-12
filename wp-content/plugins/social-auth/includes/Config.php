<?php

namespace SocialAuth;

class Config
{
    public const OPTION_KEY = 'social_auth_options';

    /**
     * @var array<string, mixed>
     */
    private array $options;

    public function __construct()
    {
        $saved = get_option(self::OPTION_KEY, []);
        $this->options = is_array($saved) ? $saved : [];
    }

    public function isProviderEnabled(string $provider): bool
    {
        $enabled = $this->options['enabled_providers'] ?? [];
        if (! is_array($enabled)) {
            return true;
        }

        return in_array($provider, $enabled, true);
    }

    public function getPostLoginRedirect(): string
    {
        $value = (string) ($this->options['post_login_redirect'] ?? '');
        return $value !== '' ? $value : home_url('/');
    }

    /**
     * @return array<string, string>
     */
    public function getProviderConfig(string $provider): array
    {
        $callbackUrl = admin_url('admin-post.php?action=social_auth_callback');

        $map = [
            'google' => [
                'GOOGLE_CLIENT_ID' => $this->get('GOOGLE_CLIENT_ID'),
                'GOOGLE_CLIENT_SECRET' => $this->get('GOOGLE_CLIENT_SECRET'),
                'GOOGLE_REDIRECT_URI' => $callbackUrl,
            ],
            'facebook' => [
                'FACEBOOK_CLIENT_ID' => $this->get('FACEBOOK_CLIENT_ID'),
                'FACEBOOK_CLIENT_SECRET' => $this->get('FACEBOOK_CLIENT_SECRET'),
                'FACEBOOK_REDIRECT_URI' => $callbackUrl,
            ],
            'apple' => [
                'APPLE_CLIENT_ID' => $this->get('APPLE_CLIENT_ID'),
                'APPLE_TEAM_ID' => $this->get('APPLE_TEAM_ID'),
                'APPLE_KEY_ID' => $this->get('APPLE_KEY_ID'),
                'APPLE_PRIVATE_KEY' => $this->get('APPLE_PRIVATE_KEY'),
                'APPLE_REDIRECT_URI' => $callbackUrl,
            ],
        ];

        return $map[$provider] ?? [];
    }

    public function get(string $key, string $default = ''): string
    {
        $fromEnv = getenv($key);
        if (is_string($fromEnv) && $fromEnv !== '') {
            return trim($fromEnv);
        }

        if (defined($key) && constant($key) !== '') {
            return (string) constant($key);
        }

        $optionKey = strtolower($key);
        $value = $this->options[$optionKey] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }
}
