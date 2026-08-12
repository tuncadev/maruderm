<?php

namespace SocialAuth\Admin;

use SocialAuth\Config;

class SettingsPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerMenu(): void
    {
        add_options_page(
            'Social Auth',
            'Social Auth',
            'manage_options',
            'social-auth',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting(Config::OPTION_KEY, Config::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => [],
        ]);

        add_settings_section('social_auth_general', 'General', '__return_false', 'social-auth');

        add_settings_field('enabled_providers', 'Enabled providers', [$this, 'renderEnabledProviders'], 'social-auth', 'social_auth_general');
        add_settings_field('post_login_redirect', 'Post-login redirect URL', [$this, 'renderTextField'], 'social-auth', 'social_auth_general', ['key' => 'post_login_redirect']);

        add_settings_section('social_auth_google', 'Google', '__return_false', 'social-auth');
        add_settings_field('google_client_id', 'Google Client ID', [$this, 'renderTextField'], 'social-auth', 'social_auth_google', ['key' => 'google_client_id']);
        add_settings_field('google_client_secret', 'Google Client Secret', [$this, 'renderTextField'], 'social-auth', 'social_auth_google', ['key' => 'google_client_secret']);

        add_settings_section('social_auth_facebook', 'Facebook', '__return_false', 'social-auth');
        add_settings_field('facebook_client_id', 'Facebook App ID', [$this, 'renderTextField'], 'social-auth', 'social_auth_facebook', ['key' => 'facebook_client_id']);
        add_settings_field('facebook_client_secret', 'Facebook App Secret', [$this, 'renderTextField'], 'social-auth', 'social_auth_facebook', ['key' => 'facebook_client_secret']);

        add_settings_section('social_auth_apple', 'Apple', '__return_false', 'social-auth');
        add_settings_field('apple_client_id', 'Apple Service ID (Client ID)', [$this, 'renderTextField'], 'social-auth', 'social_auth_apple', ['key' => 'apple_client_id']);
        add_settings_field('apple_team_id', 'Apple Team ID', [$this, 'renderTextField'], 'social-auth', 'social_auth_apple', ['key' => 'apple_team_id']);
        add_settings_field('apple_key_id', 'Apple Key ID', [$this, 'renderTextField'], 'social-auth', 'social_auth_apple', ['key' => 'apple_key_id']);
        add_settings_field('apple_private_key', 'Apple Private Key (PEM)', [$this, 'renderTextareaField'], 'social-auth', 'social_auth_apple', ['key' => 'apple_private_key']);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        return [
            'enabled_providers' => array_values(array_filter(array_map('sanitize_key', (array) ($input['enabled_providers'] ?? [])))),
            'post_login_redirect' => esc_url_raw((string) ($input['post_login_redirect'] ?? '')),
            'google_client_id' => sanitize_text_field((string) ($input['google_client_id'] ?? '')),
            'google_client_secret' => sanitize_text_field((string) ($input['google_client_secret'] ?? '')),
            'facebook_client_id' => sanitize_text_field((string) ($input['facebook_client_id'] ?? '')),
            'facebook_client_secret' => sanitize_text_field((string) ($input['facebook_client_secret'] ?? '')),
            'apple_client_id' => sanitize_text_field((string) ($input['apple_client_id'] ?? '')),
            'apple_team_id' => sanitize_text_field((string) ($input['apple_team_id'] ?? '')),
            'apple_key_id' => sanitize_text_field((string) ($input['apple_key_id'] ?? '')),
            'apple_private_key' => trim((string) ($input['apple_private_key'] ?? '')),
        ];
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>Social Auth</h1>';
        echo '<p>Use [social_auth_buttons] to show social login buttons.</p>';
        echo '<form action="options.php" method="post">';
        settings_fields(Config::OPTION_KEY);
        do_settings_sections('social-auth');
        submit_button('Save Settings');
        echo '</form>';
        echo '</div>';
    }

    /**
     * @param array{key: string} $args
     */
    public function renderTextField(array $args): void
    {
        $key = $args['key'];
        $options = get_option(Config::OPTION_KEY, []);
        $value = is_array($options) ? (string) ($options[$key] ?? '') : '';

        printf(
            '<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" autocomplete="off" />',
            esc_attr(Config::OPTION_KEY),
            esc_attr($key),
            esc_attr($value)
        );
    }

    /**
     * @param array{key: string} $args
     */
    public function renderTextareaField(array $args): void
    {
        $key = $args['key'];
        $options = get_option(Config::OPTION_KEY, []);
        $value = is_array($options) ? (string) ($options[$key] ?? '') : '';

        printf(
            '<textarea class="large-text code" rows="8" name="%1$s[%2$s]">%3$s</textarea>',
            esc_attr(Config::OPTION_KEY),
            esc_attr($key),
            esc_textarea($value)
        );
    }

    public function renderEnabledProviders(): void
    {
        $options = get_option(Config::OPTION_KEY, []);
        $enabled = is_array($options) ? (array) ($options['enabled_providers'] ?? ['google', 'facebook', 'apple']) : ['google', 'facebook', 'apple'];

        foreach (['google', 'facebook', 'apple'] as $provider) {
            printf(
                '<label><input type="checkbox" name="%1$s[enabled_providers][]" value="%2$s" %3$s /> %4$s</label><br>',
                esc_attr(Config::OPTION_KEY),
                esc_attr($provider),
                checked(in_array($provider, $enabled, true), true, false),
                esc_html(ucfirst($provider))
            );
        }
    }
}
