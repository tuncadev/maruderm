<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Media
{
    public function publicUrl(string $url): string
    {
        // Clone upload paths match production. Validation must still fetch the public file.
        $localUploads = 'https://maruderm.dev/wp-content/uploads/';
        return str_starts_with($url, $localUploads)
            ? 'https://wp.maruderm.com.ua/wp-content/uploads/' . substr($url, strlen($localUploads))
            : $url;
    }

    public function validate(array $offers): int
    {
        $urls = [];
        foreach ($offers as $offer) {
            foreach ($offer['images'] as $url) { $urls[$url] = true; }
        }
        foreach (array_keys($urls) as $url) {
            $parts = wp_parse_url($url);
            if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
                || ! in_array($parts['host'] ?? '', ['wp.maruderm.com.ua', 'www.maruderm.com.ua', 'maruderm.com.ua'], true)
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
                || (isset($parts['port']) && $parts['port'] !== 443) || preg_match('/[^\x21-\x7e]/', $url)) {
                throw new \RuntimeException('Image URL must be a direct public HTTPS Maruderm URL.');
            }
            $response = wp_safe_remote_get($url, ['timeout' => 15, 'redirection' => 0, 'limit_response_size' => 10485761]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200
                || ! str_starts_with((string) wp_remote_retrieve_header($response, 'content-type'), 'image/')) {
                throw new \RuntimeException('Image retrieval failed; previous feed retained.');
            }
            $body = wp_remote_retrieve_body($response);
            if (strlen($body) > 10485760 || ! @getimagesizefromstring($body)) {
                throw new \RuntimeException('Image is invalid or exceeds 10 MB.');
            }
        }
        return count($urls);
    }
}
