<?php

declare(strict_types=1);

namespace Maruderm\Catalog;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class CatalogRoutes implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_action('template_redirect', [$this, 'redirectLegacyShopPath'], 0);
        add_action('template_redirect', [$this, 'redirectLegacyCategoryPath'], 0);
        add_action('template_redirect', [$this, 'redirectCategoryQuery'], 0);
        add_action('wp', [$this, 'removeLegacyCatalogHeader'], 20);
    }

    public function redirectLegacyShopPath(): void
    {
        $request_path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

        if (!is_string($request_path) || untrailingslashit($request_path) !== '/shop') {
            return;
        }

        $this->redirect(wc_get_page_permalink('shop'));
    }

    public function redirectLegacyCategoryPath(): void
    {
        $request_path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

        if (!is_string($request_path) || !str_starts_with($request_path, '/kategoria-tovaru/')) {
            return;
        }

        $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));
        $slug = sanitize_title((string) end($segments));
        $category = $slug !== '' ? get_term_by('slug', $slug, 'product_cat') : false;

        if (!$category instanceof \WP_Term) {
            return;
        }

        $target_url = get_term_link($category);

        if (!is_wp_error($target_url)) {
            $this->redirect($target_url);
        }
    }

    public function removeLegacyCatalogHeader(): void
    {
        if (is_shop() || is_product_taxonomy()) {
            remove_action('martfury_after_header', 'martfury_page_header');
        }
    }

    public function redirectCategoryQuery(): void
    {
        if (!is_shop() || !isset($_GET['category'])) {
            return;
        }

        $slug = sanitize_title(wp_unslash((string) $_GET['category']));
        $category = $slug !== '' ? get_term_by('slug', $slug, 'product_cat') : false;

        if (!$category instanceof \WP_Term) {
            return;
        }

        $target_url = get_term_link($category);

        if (!is_wp_error($target_url)) {
            unset($_GET['category']);
            $this->redirect($target_url);
        }
    }

    private function redirect(string $target_url): void
    {
        $query = wp_unslash($_GET);

        if ($query !== []) {
            $target_url = add_query_arg($query, $target_url);
        }

        wp_safe_redirect($target_url, 301, 'Maruderm Catalog');
        exit;
    }
}
