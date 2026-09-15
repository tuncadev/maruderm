<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Endpoint
{
    public function __construct(private Settings $settings, private Storage $storage) {}

    /** Pure route decision for exact file access and regression checks. */
    public function access(string $path, string $method): ?int
    {
        $base = rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/') . '/kasta-feed';
        if ($path !== $base && ! str_starts_with($path, $base . '/')) { return null; }
        $settings = $this->settings->get();
        if (! $settings['enabled'] || ! preg_match('#^' . preg_quote($base, '#') . '/([a-f0-9]{64})/products\.xml$#D', $path, $match)
            || $settings['key'] === '' || ! hash_equals($settings['key'], $match[1])) {
            return 404;
        }
        return in_array($method, ['GET', 'HEAD'], true) ? 200 : 405;
    }

    public function serve(): void
    {
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $status = $this->access($path, $method);
        if ($status === null) { return; }
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        if ($status !== 200) {
            status_header($status);
            if ($status === 405) { header('Allow: GET, HEAD'); }
            exit;
        }
        try {
            $file = $this->storage->path('products.xml');
            $handle = is_file($file) ? fopen($file, 'rb') : false;
            if ($handle === false) { status_header(503); header('Retry-After: 900'); exit; }
            $stat = fstat($handle);
            $maxAge = (int) $this->settings->get()['max_age_hours'] * 3600;
            if (! $stat || $stat['size'] === 0 || time() - $stat['mtime'] > $maxAge) {
                fclose($handle); status_header(503); header('Retry-After: 900'); exit;
            }
            status_header(200);
            header('Content-Type: application/xml; charset=UTF-8');
            header('Content-Disposition: inline; filename="products.xml"');
            header('Content-Length: ' . $stat['size']);
            if ($method === 'GET') { fpassthru($handle); }
            fclose($handle);
        } catch (\Throwable $error) {
            status_header(503);
            header('Retry-After: 900');
        }
        exit;
    }
}
