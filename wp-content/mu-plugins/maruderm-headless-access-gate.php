<?php
/**
 * Keeps the WordPress frontend private while preserving headless integrations.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Headless_Access_Gate
{
    private const LOGIN_SLUG = 'orangejuice';

    /** @var list<string> */
    private const PUBLIC_SYSTEM_PATHS = [
        '/graphql',
        '/index.php/graphql',
        '/wp-json',
        '/wc-api',
        '/wp-cron.php',
        '/xmlrpc.php',
        '/wp-admin/admin-ajax.php',
        '/wp-admin/admin-post.php',
        '/wp-admin/load-scripts.php',
        '/wp-admin/load-styles.php',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'serveLoginEntry'], -1000);
        add_action('init', [$this, 'concealGuestAdmin'], -900);
        add_action('login_init', [$this, 'concealDefaultLogin'], -1000);
        add_action('admin_init', [$this, 'concealGuestAdmin'], -1000);
        add_action('template_redirect', [$this, 'redirectPublicFrontend'], -1000);
        add_filter('site_url', [$this, 'rewriteSiteLoginUrl'], 10, 4);
        add_filter('network_site_url', [$this, 'rewriteNetworkLoginUrl'], 10, 3);
    }

    public function serveLoginEntry(): void
    {
        if ($this->requestPath() !== '/' . self::LOGIN_SLUG) {
            return;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash((string) $_REQUEST['action'])) : '';

        if (is_user_logged_in() && $action === '') {
            wp_safe_redirect(admin_url());
            exit;
        }

        $GLOBALS['pagenow'] = 'wp-login.php';
        require ABSPATH . 'wp-login.php';
        exit;
    }

    public function concealDefaultLogin(): void
    {
        if ($this->requestPath() === '/wp-login.php') {
            $this->renderTransitionScreen(404);
        }
    }

    public function concealGuestAdmin(): void
    {
        if (! str_starts_with($this->requestPath(), '/wp-admin')
            || is_user_logged_in()
            || $this->isPublicSystemRequest()
        ) {
            return;
        }

        $this->renderTransitionScreen(404);
    }

    public function redirectPublicFrontend(): void
    {
        if (is_user_logged_in() || $this->isPublicSystemRequest()) {
            return;
        }

        $frontend_url = $this->frontendUrl();

        if ($frontend_url === '') {
            $this->renderTransitionScreen(503);
        }

        $request_uri = isset($_SERVER['REQUEST_URI'])
            ? wp_unslash((string) $_SERVER['REQUEST_URI'])
            : '/';
        $target = $frontend_url . '/' . ltrim($request_uri, '/');

        wp_redirect(esc_url_raw($target), 302, 'Maruderm Headless Access Gate');
        exit;
    }

    /**
     * @param mixed $url
     * @param mixed $path
     * @param mixed $scheme
     * @param mixed $blog_id
     */
    public function rewriteSiteLoginUrl($url, $path, $scheme, $blog_id): string
    {
        return $this->rewriteLoginUrl((string) $url);
    }

    /**
     * @param mixed $url
     * @param mixed $path
     * @param mixed $scheme
     */
    public function rewriteNetworkLoginUrl($url, $path, $scheme): string
    {
        return $this->rewriteLoginUrl((string) $url);
    }

    private function rewriteLoginUrl(string $url): string
    {
        return str_replace('/wp-login.php', '/' . self::LOGIN_SLUG, $url);
    }

    private function isPublicSystemRequest(): bool
    {
        if ((defined('REST_REQUEST') && REST_REQUEST)
            || (defined('DOING_AJAX') && DOING_AJAX)
            || (defined('DOING_CRON') && DOING_CRON)
            || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        ) {
            return true;
        }

        if (isset($_GET['wc-ajax']) || isset($_GET['rest_route'])) {
            return true;
        }

        $path = $this->requestPath();

        foreach (self::PUBLIC_SYSTEM_PATHS as $system_path) {
            if ($path === $system_path || str_starts_with($path, $system_path . '/')) {
                return true;
            }
        }

        return false;
    }

    private function requestPath(): string
    {
        $request_uri = isset($_SERVER['REQUEST_URI'])
            ? wp_unslash((string) $_SERVER['REQUEST_URI'])
            : '/';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        return '/' . trim($path, '/');
    }

    private function frontendUrl(): string
    {
        if (! function_exists('maruderm_headless_frontend_url')) {
            return '';
        }

        $url = maruderm_headless_frontend_url();
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);
        $host = wp_parse_url($url, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
            return '';
        }

        return rtrim($url, '/');
    }

    private function renderTransitionScreen(int $status): void
    {
        $frontend_url = $this->frontendUrl();
        $target = $frontend_url !== '' ? $frontend_url : home_url('/');

        status_header($status);
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow', true);

        echo '<!doctype html><html lang="uk"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<meta name="robots" content="noindex,nofollow">';
        echo '<meta http-equiv="refresh" content="4;url=' . esc_url($target) . '">';
        echo '<title>Maruderm</title><style>';
        echo '*,*::before,*::after{box-sizing:border-box}html,body{min-height:100%;margin:0}';
        echo 'body{display:grid;place-items:center;overflow:hidden;padding:24px;color:#173329;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#e8fff5}';
        echo 'body::before{content:"";position:fixed;inset:-25%;background:radial-gradient(circle at 18% 22%,rgba(255,173,193,.88),transparent 30%),radial-gradient(circle at 78% 18%,rgba(255,224,122,.82),transparent 28%),radial-gradient(circle at 72% 78%,rgba(132,224,203,.9),transparent 34%),radial-gradient(circle at 20% 82%,rgba(154,181,255,.82),transparent 31%);filter:blur(32px);animation:drift 10s ease-in-out infinite alternate}';
        echo '.card{position:relative;width:min(520px,100%);padding:42px 34px;text-align:center;border:1px solid rgba(255,255,255,.72);border-radius:28px;background:rgba(255,255,255,.64);box-shadow:0 28px 80px rgba(34,76,63,.18);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px)}';
        echo '.mark{display:grid;place-items:center;width:58px;height:58px;margin:0 auto 22px;border-radius:50%;background:#173329;color:#fff;font-size:25px;font-weight:800;box-shadow:0 12px 30px rgba(23,51,41,.22)}';
        echo 'h1{margin:0 0 12px;font-size:clamp(28px,7vw,42px);line-height:1.05;letter-spacing:-.04em}p{margin:0 auto 26px;max-width:390px;font-size:16px;line-height:1.6;color:#49675d}';
        echo 'a{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 24px;border-radius:999px;background:#173329;color:#fff;text-decoration:none;font-weight:700;box-shadow:0 12px 28px rgba(23,51,41,.2)}';
        echo '@keyframes drift{to{transform:translate3d(3%,-2%,0) scale(1.06)}}@media(prefers-reduced-motion:reduce){body::before{animation:none}}';
        echo '</style></head><body><main class="card"><span class="mark">M</span>';
        echo '<h1>Ми вже на новій вітрині</h1>';
        echo '<p>Ця адреса працює як внутрішня система Maruderm. Зараз перенаправимо тебе до магазину.</p>';
        echo '<a href="' . esc_url($target) . '">Перейти до Maruderm</a></main></body></html>';
        exit;
    }
}

(new Maruderm_Headless_Access_Gate())->register();
