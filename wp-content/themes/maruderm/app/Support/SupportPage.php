<?php

declare(strict_types=1);

namespace Maruderm\Support;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns support-page routing and parent-theme layout removal. */
final class SupportPage implements Registrable
{
    use Loadable;

    private const SLUGS = ['dostavka-i-oplata', 'povernennya', 'kontakty', 'faq'];

    public function register(): void
    {
        add_action('wp', [$this, 'removeInheritedLayout'], 20);
        add_filter('body_class', [$this, 'addBodyClass']);
        add_filter('template_include', [$this, 'useSupportTemplate'], 20);
        add_filter('document_title_parts', [$this, 'filterDocumentTitle']);
        add_action('wp_head', [$this, 'renderMetaDescription'], 1);
    }

    public static function isCurrent(): bool
    {
        return is_page(self::SLUGS);
    }

    public static function currentSlug(): ?string
    {
        $page = get_queried_object();
        return $page instanceof \WP_Post && in_array($page->post_name, self::SLUGS, true) ? $page->post_name : null;
    }

    public function removeInheritedLayout(): void
    {
        if (!self::isCurrent()) {
            return;
        }

        remove_action('martfury_after_header', 'martfury_page_header');
        remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
        remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
    }

    /** @param string[] $classes */
    public function addBodyClass(array $classes): array
    {
        if (self::isCurrent()) {
            $classes[] = 'legal-page';
            $classes[] = 'support-page';
        }
        return $classes;
    }

    public function useSupportTemplate(string $template): string
    {
        if (!self::isCurrent()) {
            return $template;
        }

        $support_template = get_stylesheet_directory() . '/page-support.php';
        return is_readable($support_template) ? $support_template : $template;
    }

    /** @param array<string, string> $parts */
    public function filterDocumentTitle(array $parts): array
    {
        $page = $this->currentPage();
        if (is_array($page)) {
            $parts['title'] = (string) $page['shortTitle'];
        }
        return $parts;
    }

    public function renderMetaDescription(): void
    {
        $page = $this->currentPage();
        if (is_array($page)) {
            echo '<meta name="description" content="' . esc_attr((string) $page['description']) . '">' . "\n";
        }
    }

    /** @return array<string, mixed>|null */
    private function currentPage(): ?array
    {
        $slug = self::currentSlug();
        return $slug === null ? null : (new SupportPageRepository())->find($slug);
    }
}
