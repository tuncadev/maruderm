<?php

namespace SocialAuth;

use Throwable;
use SocialAuth\Admin\SettingsPage;

class Plugin
{
    private static ?self $instance = null;

    private Config $config;

    private ProviderFactory $factory;

    private AuthCore $authCore;

    public static function boot(string $pluginFile): void
    {
        if (self::$instance instanceof self) {
            return;
        }

        self::$instance = new self();
        self::$instance->registerHooks($pluginFile);
    }

    private function __construct()
    {
        $this->config = new Config();
        $this->factory = new ProviderFactory($this->config);
        $this->authCore = new AuthCore();
    }

    private function registerHooks(string $pluginFile): void
    {
        add_action('admin_post_nopriv_social_auth_start', [$this, 'handleStart']);
        add_action('admin_post_social_auth_start', [$this, 'handleStart']);
        add_action('admin_post_nopriv_social_auth_callback', [$this, 'handleCallback']);
        add_action('admin_post_social_auth_callback', [$this, 'handleCallback']);

        add_shortcode('social_auth_buttons', [$this, 'renderButtonsShortcode']);

        $settings = new SettingsPage();
        $settings->register();

        register_activation_hook($pluginFile, [$this, 'onActivate']);
    }

    public function onActivate(): void
    {
        $current = get_option(Config::OPTION_KEY, []);

        if (! is_array($current) || ! isset($current['enabled_providers'])) {
            $defaults = [
                'enabled_providers' => ['google', 'facebook', 'apple'],
                'post_login_redirect' => home_url('/'),
            ];

            update_option(Config::OPTION_KEY, is_array($current) ? array_merge($defaults, $current) : $defaults);
        }
    }

    public function handleStart(): void
    {
        $provider = sanitize_key((string) ($_GET['provider'] ?? ''));
        $mode = sanitize_key((string) ($_GET['mode'] ?? 'login'));
        $popup = isset($_GET['popup']) && (string) $_GET['popup'] === '1';
        if (! in_array($mode, ['login', 'register'], true)) {
            $mode = 'login';
        }
        if ($provider === '') {
            $this->failRedirect('missing_provider');
        }

        $redirectTo = isset($_GET['redirect_to'])
            ? esc_url_raw((string) $_GET['redirect_to'])
            : $this->config->getPostLoginRedirect();
        $successRedirectTo = isset($_GET['success_redirect'])
            ? esc_url_raw((string) $_GET['success_redirect'])
            : $redirectTo;

        try {
            $providerInstance = $this->factory->make($provider);
            $state = wp_generate_password(32, false, false);

            set_transient('social_auth_state_' . $state, [
                'provider' => $provider,
                'mode' => $mode,
                'popup' => $popup,
                'redirect_to' => $redirectTo,
                'success_redirect_to' => $successRedirectTo,
            ], 10 * MINUTE_IN_SECONDS);

            // OAuth provider URLs are external by design; safe_redirect would force fallback to wp-admin.
            wp_redirect($providerInstance->getAuthorizationUrl($state));
            exit;
        } catch (Throwable $exception) {
            $this->failRedirect('start_failed', $redirectTo, $mode);
        }
    }

    public function handleCallback(): void
    {
        $state = sanitize_text_field((string) ($_REQUEST['state'] ?? ''));
        $code = sanitize_text_field((string) ($_REQUEST['code'] ?? ''));

        if ($state === '' || $code === '') {
            $this->failRedirect('invalid_callback');
        }

        $stored = get_transient('social_auth_state_' . $state);
        delete_transient('social_auth_state_' . $state);

        if (! is_array($stored)) {
            $this->failRedirect('invalid_state');
        }
        $provider = sanitize_key((string) ($stored['provider'] ?? ''));
        if ($provider === '') {
            $this->failRedirect('invalid_state');
        }

        $mode = sanitize_key((string) ($stored['mode'] ?? 'login'));
        if (! in_array($mode, ['login', 'register'], true)) {
            $mode = 'login';
        }
        $popup = isset($stored['popup']) && (bool) $stored['popup'];

        $redirectTo = isset($stored['redirect_to']) ? esc_url_raw((string) $stored['redirect_to']) : '';
        if ($redirectTo === '') {
            $redirectTo = $this->config->getPostLoginRedirect();
        }
        $successRedirectTo = isset($stored['success_redirect_to']) ? esc_url_raw((string) $stored['success_redirect_to']) : '';
        if ($successRedirectTo === '') {
            $successRedirectTo = $redirectTo;
        }

        try {
            $providerInstance = $this->factory->make($provider);
            $userData = $providerInstance->fetchUserData($code);
            $this->authCore->authenticate($userData, $mode);

            if ($popup) {
                $this->renderPopupResult($successRedirectTo, '', $mode);
            }

            wp_safe_redirect($successRedirectTo);
            exit;
        } catch (Throwable $exception) {
            $error = $mode === 'login' ? 'social_login_failed' : 'social_register_failed';
            if ($popup) {
                $this->renderPopupResult($redirectTo, $error, $mode);
            }
            $this->failRedirect($error, $redirectTo, $mode);
        }
    }

    public function renderButtonsShortcode(): string
    {
        $atts = shortcode_atts([
            'mode' => 'login',
        ], [], 'social_auth_buttons');
        $mode = sanitize_key((string) $atts['mode']);
        if (! in_array($mode, ['login', 'register'], true)) {
            $mode = 'login';
        }

        $providers = ['google', 'facebook', 'apple'];
        $output = '<div class="social-auth-buttons">';

        foreach ($providers as $provider) {
            if (! $this->config->isProviderEnabled($provider)) {
                continue;
            }

            $url = add_query_arg([
                'action' => 'social_auth_start',
                'provider' => $provider,
                'mode' => $mode,
                'popup' => '1',
                'redirect_to' => $this->currentUrl(),
                'success_redirect' => $this->config->getPostLoginRedirect(),
            ], admin_url('admin-post.php'));

            $output .= sprintf(
                '<p><a class="button" href="%s">%s with %s</a></p>',
                esc_url($url),
                esc_html(ucfirst($mode)),
                esc_html(ucfirst($provider))
            );
        }

        $output .= '</div>';

        return $output;
    }

    private function failRedirect(string $reason, string $redirectTo = '', string $mode = 'login'): void
    {
        if ($redirectTo === '') {
            $redirectTo = wp_login_url();
        }

        $tab = $mode === 'register' ? 'register' : 'login';
        $url = add_query_arg([
            'social_auth_error' => rawurlencode($reason),
            'tab' => $tab,
        ], $redirectTo);
        wp_safe_redirect($url);
        exit;
    }

    private function renderPopupResult(string $redirectTo, string $error = '', string $mode = 'login'): void
    {
        $redirectTarget = $redirectTo !== '' ? $redirectTo : $this->config->getPostLoginRedirect();

        if ($error !== '') {
            $tab = $mode === 'register' ? 'register' : 'login';
            $redirectTarget = add_query_arg([
                'social_auth_error' => rawurlencode($error),
                'tab' => $tab,
            ], $redirectTarget);
        }

        $payload = [
            'source' => 'social-auth',
            'status' => $error === '' ? 'success' : 'error',
            'redirectTo' => esc_url_raw($redirectTarget),
            'error' => $error,
            'mode' => $mode,
        ];

        status_header(200);
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Social Auth</title></head><body>';
        echo '<script>';
        echo 'var payload=' . wp_json_encode($payload) . ';';
        echo 'if(window.opener&&!window.opener.closed){window.opener.postMessage(payload,window.location.origin);}';
        echo 'window.close();';
        echo 'setTimeout(function(){window.location.href=payload.redirectTo;},600);';
        echo '</script>';
        echo '</body></html>';
        exit;
    }

    private function currentUrl(): string
    {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';

        if ($host === '') {
            return home_url('/');
        }

        return $scheme . '://' . $host . $uri;
    }
}
