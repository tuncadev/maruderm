<?php

declare(strict_types=1);

namespace Maruderm\Kernel;

use Maruderm\WooCommerce\ProductImageRepository;

if (!defined('ABSPATH')) {
    exit();
}

final class Enqueue implements Registrable
{
    use Loadable;

    private const MANIFEST_FILE = 'manifest.json';
    private const DEV_SERVER_CLIENT = '@vite/client';
    private const CRITICAL_FONT_NAMES = [
        'RozetkaWeb-Regular',
        'RozetkaWeb-Bold',
    ];

    private const ENTRYPOINTS = [
        'globals' => 'assets/globals/index.js',
        'frontend' => 'assets/frontend/index.js',
        'catalog' => 'assets/catalog/index.js',
        'product' => 'assets/product/index.js',
        'cart' => 'assets/cart/index.js',
        'delivery' => 'assets/delivery/index.js',
        'payment' => 'assets/payment/index.js',
        'bank-transfer' => 'assets/bank-transfer/index.js',
        'checkout-result' => 'assets/checkout-result/index.js',
        'landing-page' => 'assets/landing-page/index.js',
        'home' => 'assets/home/index.js',
        'footer' => 'assets/footer/index.js',
        'hair-analysis' => 'assets/hair-analysis/index.js',
        'login' => 'assets/login/index.js',
        'account' => 'assets/account/index.js',
    ];

    private ?array $manifest = null;
    private ?array $critical_font_urls = null;
    private ?string $dev_server_url = null;
    private bool $dev_server_url_resolved = false;
    private ProductImageRepository $product_images;
    /** @var array<string, true> */
    private array $module_script_handles = [];

    public function __construct(?ProductImageRepository $product_images = null)
    {
        $this->product_images = $product_images ?? new ProductImageRepository();
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);
        add_filter('wp_preload_resources', [$this, 'preload_resources']);
        add_filter('script_loader_tag', [$this, 'addModuleTypeAttribute'], 10, 2);
    }

    public function enqueue_assets(): void
    {
        if ($this->enqueue_dev_assets()) {
            return;
        }

        foreach (self::ENTRYPOINTS as $handle => $entrypoint) {
            if (!$this->should_enqueue_entrypoint($handle)) {
                continue;
            }

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

        $this->registerModuleScript('maruderm-vite-client');

        foreach (self::ENTRYPOINTS as $handle => $entrypoint) {
            if (!$this->should_enqueue_entrypoint($handle)) {
                continue;
            }

            $script_handle = sprintf('maruderm-%s', $handle);

            wp_enqueue_script(
                $script_handle,
                $this->dev_server_asset_url($dev_server_url, $entrypoint),
                ['maruderm-vite-client'],
                null,
                true
            );

            $this->registerModuleScript($script_handle);

            if ($handle === 'hair-analysis') {
                wp_localize_script($script_handle, 'marudermHairAnalysisProducts', $this->hairAnalysisProducts());
            }
        }

        return true;
    }

    private function should_enqueue_entrypoint(string $handle): bool
    {
        if ($handle === 'catalog') {
            return function_exists('is_shop')
                && (is_shop() || is_product_taxonomy() || (is_search() && get_query_var('post_type') === 'product'));
        }

        if ($handle === 'product') {
            return function_exists('is_product') && is_product();
        }

        if ($handle === 'cart') {
            return function_exists('is_cart') && is_cart();
        }

        if ($handle === 'delivery') {
            $step = isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : 'delivery';

            return function_exists('is_checkout')
                && is_checkout()
                && !is_order_received_page()
                && $step !== 'payment';
        }

        if ($handle === 'payment') {
            $step = isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : 'delivery';

            return function_exists('is_checkout')
                && is_checkout()
                && !is_order_received_page()
                && $step === 'payment';
        }

        if ($handle === 'bank-transfer') {
            return \Maruderm\Checkout\CheckoutResultPage::isBankTransferView();
        }

        if ($handle === 'checkout-result') {
            return \Maruderm\Checkout\CheckoutResultPage::currentOrder() instanceof \WC_Order
                && !\Maruderm\Checkout\CheckoutResultPage::isBankTransferView();
        }

        if ($handle === 'landing-page') {
            return is_page_template('page-landing-page.php')
                || is_page('landing-page');
        }

        if ($handle === 'home') {
            return is_front_page();
        }

        if ($handle === 'footer') {
            return true;
        }

        if ($handle === 'hair-analysis') {
            return \Maruderm\HairAnalysis\HairAnalysisPage::isCurrent()
                || is_page_template('page-hair-analysis.php')
                || is_page('hair-analysis');
        }

        if ($handle === 'login') {
            return !is_user_logged_in() && !is_page_template('template-coming-soon-page.php');
        }

        if ($handle === 'account') {
            return is_user_logged_in()
                && function_exists('is_account_page')
                && is_account_page();
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
            foreach ($this->cssFilesInCascadeOrder($asset['css'], $manifest) as $index => $css_file) {
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

        $this->registerModuleScript($script_handle);

        if ($handle === 'hair-analysis') {
            wp_localize_script($script_handle, 'marudermHairAnalysisProducts', $this->hairAnalysisProducts());
        }
    }

    public function addModuleTypeAttribute(string $tag, string $handle): string
    {
        if (!isset($this->module_script_handles[$handle]) || str_contains($tag, 'type="module"')) {
            return $tag;
        }

        return str_replace('<script ', '<script type="module" ', $tag);
    }

    private function registerModuleScript(string $handle): void
    {
        $this->module_script_handles[$handle] = true;
        wp_script_add_data($handle, 'type', 'module');
    }

    /** @return array<int, array<string, int|string>> */
    private function hairAnalysisProducts(): array
    {
        if (!function_exists('wc_get_product')) {
            return [];
        }

        $subcategories = [
            6007 => 'conditioner',
            6009 => 'styling',
            6011 => 'scalp-tonic',
            6013 => 'shampoo',
            6034 => 'scalp-tonic',
        ];
        $products = [];

        foreach ($subcategories as $productId => $subcategory) {
            $product = wc_get_product($productId);

            if (!$product instanceof \WC_Product || $product->get_status() !== 'publish') {
                continue;
            }

            $categoryNames = wp_get_post_terms($productId, 'product_cat', ['fields' => 'names']);
            $products[] = [
                'id' => $productId,
                'name' => $product->get_name(),
                'price' => (int) round((float) $product->get_price()),
                'image' => $this->product_images->primaryUrl($product, 'woocommerce_thumbnail'),
                'url' => $product->get_permalink(),
                'categoryLabel' => !is_wp_error($categoryNames) && $categoryNames !== [] ? $categoryNames[0] : 'Догляд за волоссям',
                'subcategory' => $subcategory,
            ];
        }

        return $products;
    }

    /**
     * @param array<int, string> $css_files
     * @param array<string, array<string, mixed>> $manifest
     * @return array<int, string>
     */
    private function cssFilesInCascadeOrder(array $css_files, array $manifest): array
    {
        $shared_files = [];

        foreach ($manifest as $key => $entry) {
            if (!str_starts_with((string) $key, '_') || empty($entry['file'])) {
                continue;
            }

            $shared_files[(string) $entry['file']] = true;
        }

        $indexed_files = [];

        foreach ($css_files as $index => $css_file) {
            $priority = str_contains($css_file, 'storefront-foundation-')
                ? 0
                : (isset($shared_files[$css_file]) ? 1 : 2);
            $indexed_files[] = [
                'file' => $css_file,
                'index' => $index,
                'priority' => $priority,
            ];
        }

        usort($indexed_files, static function (array $left, array $right): int {
            return [$left['priority'], $left['index']] <=> [$right['priority'], $right['index']];
        });

        return array_values(array_column($indexed_files, 'file'));
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
