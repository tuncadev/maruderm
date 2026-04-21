<?php

declare(strict_types=1);

namespace Maruderm\Kernel;

if (!defined('ABSPATH')) {
    exit();
}

final class Enqueue implements Registrable
{
    use Loadable;

    private const MANIFEST_FILE = 'manifest.json';
    private const DEV_SERVER_CLIENT = '@vite/client';
    private const CRITICAL_FONT_NAMES = [
        'FixelText-Regular',
        'FixelDisplay-Regular',
    ];

    private const ENTRYPOINTS = [
        'globals' => 'assets/globals/index.js',
        'frontend' => 'assets/frontend/index.js',
    ];

    private ?array $manifest = null;
    private ?array $critical_font_urls = null;
    private ?string $dev_server_url = null;
    private bool $dev_server_url_resolved = false;

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);
        add_filter('wp_preload_resources', [$this, 'preload_resources']);
    }

    public function enqueue_assets(): void
    {
        if ($this->enqueue_dev_assets()) {
            return;
        }

        foreach (self::ENTRYPOINTS as $handle => $entrypoint) {
            $this->enqueue_entrypoint($handle, $entrypoint);
        }
    }

    private function enqueue_dev_assets(): bool
    {
        $dev_server_url = $this->get_dev_server_url();

        if ($dev_server_url === null) {
            return false;
        }

        wp_enqueue_script(
            'maruderm-vite-client',
            $this->dev_server_asset_url($dev_server_url, self::DEV_SERVER_CLIENT),
            [],
            null,
            false
        );

        wp_script_add_data('maruderm-vite-client', 'type', 'module');

        foreach (self::ENTRYPOINTS as $handle => $entrypoint) {
            $script_handle = sprintf('maruderm-%s', $handle);

            wp_enqueue_script(
                $script_handle,
                $this->dev_server_asset_url($dev_server_url, $entrypoint),
                ['maruderm-vite-client'],
                null,
                true
            );

            wp_script_add_data($script_handle, 'type', 'module');
        }

        return true;
    }

    private function enqueue_entrypoint(string $handle, string $entrypoint): void
    {
        $manifest = $this->get_manifest();

        if ($manifest === null || !isset($manifest[$entrypoint])) {
            return;
        }

        $asset = $manifest[$entrypoint];

        if (!empty($asset['css']) && is_array($asset['css'])) {
            foreach ($asset['css'] as $index => $css_file) {
                wp_enqueue_style(
                    sprintf('maruderm-%s-%d', $handle, $index),
                    Helpers::dist_uri($css_file),
                    [],
                    $this->asset_version($css_file)
                );
            }
        }

        if (empty($asset['file'])) {
            return;
        }

        $script_handle = sprintf('maruderm-%s', $handle);

        wp_enqueue_script(
            $script_handle,
            Helpers::dist_uri($asset['file']),
            [],
            $this->asset_version($asset['file']),
            true
        );

        wp_script_add_data($script_handle, 'type', 'module');
    }

    public function preload_resources(array $preloads): array
    {
        if ($this->get_dev_server_url() !== null) {
            return $preloads;
        }

        foreach ($this->get_critical_font_urls() as $font_url) {
            $preloads[] = [
                'href' => $font_url,
                'as' => 'font',
                'type' => 'font/woff2',
                'crossorigin' => 'anonymous',
            ];
        }

        return $preloads;
    }

    private function get_manifest(): ?array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifest_path = Helpers::dist_path(self::MANIFEST_FILE);

        if (!file_exists($manifest_path)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifest_path), true);

        if (!is_array($manifest)) {
            return null;
        }

        $this->manifest = $manifest;

        return $this->manifest;
    }

    private function get_dev_server_url(): ?string
    {
        if ($this->dev_server_url_resolved) {
            return $this->dev_server_url;
        }

        $server_url = '';
        $hot_file_path = Helpers::vite_hot_path();

        if (file_exists($hot_file_path)) {
            $server_url = trim((string) file_get_contents($hot_file_path));
        }

        $server_url = apply_filters('maruderm/vite_dev_server_url', $server_url);

        if (!is_string($server_url) || trim($server_url) === '') {
            $this->dev_server_url_resolved = true;

            return null;
        }

        $this->dev_server_url = untrailingslashit(trim($server_url));
        $this->dev_server_url_resolved = true;

        return $this->dev_server_url;
    }

    private function get_critical_font_urls(): array
    {
        if ($this->critical_font_urls !== null) {
            return $this->critical_font_urls;
        }

        $manifest = $this->get_manifest();

        if ($manifest === null || empty($manifest[self::ENTRYPOINTS['globals']]['css'])) {
            $this->critical_font_urls = [];

            return $this->critical_font_urls;
        }

        $font_urls = $this->match_critical_font_urls_from_assets(
            $manifest[self::ENTRYPOINTS['globals']]['assets'] ?? []
        );

        if ($font_urls === []) {
            foreach ($manifest[self::ENTRYPOINTS['globals']]['css'] as $css_file) {
                $css_path = Helpers::dist_path($css_file);

                if (!file_exists($css_path)) {
                    continue;
                }

                $css_contents = (string) file_get_contents($css_path);
                preg_match_all('/url\\((["\']?)([^)"\']+\\.woff2)\\1\\)/i', $css_contents, $matches);

                $font_urls += $this->match_critical_font_urls_from_assets($matches[2] ?? []);
            }
        }

        $this->critical_font_urls = array_values($font_urls);

        return $this->critical_font_urls;
    }

    private function match_critical_font_urls_from_assets(array $assets): array
    {
        $font_urls = [];

        foreach ($assets as $asset) {
            foreach (self::CRITICAL_FONT_NAMES as $font_name) {
                if (!str_contains($asset, $font_name)) {
                    continue;
                }

                $font_urls[$font_name] = $this->normalize_dist_asset_url($asset);
            }
        }

        return $font_urls;
    }

    private function normalize_dist_asset_url(string $asset_url): string
    {
        if (preg_match('#^(?:https?:)?//#i', $asset_url) === 1) {
            return $asset_url;
        }

        return str_starts_with($asset_url, '/')
            ? home_url($asset_url)
            : Helpers::dist_uri($asset_url);
    }

    private function dev_server_asset_url(string $server_url, string $asset_path): string
    {
        return trailingslashit($server_url) . ltrim($asset_path, '/');
    }

    private function asset_version(string $relative_path): string
    {
        $absolute_path = Helpers::dist_path($relative_path);

        if (file_exists($absolute_path)) {
            return (string) filemtime($absolute_path);
        }

        return (string) wp_get_theme()->get('Version');
    }
}
