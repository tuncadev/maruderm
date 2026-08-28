<?php

declare(strict_types=1);

namespace Maruderm\Legal;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns legal page routing, document metadata and parent-theme layout removal. */
final class LegalDocumentPage implements Registrable
{
    use Loadable;

    /** @var array<string, string> */
    private const PAGE_KEYS = [
        'public-offer' => 'publicOffer',
        'terms-and-privacy' => 'termsAndPrivacy',
    ];

    public function register(): void
    {
        add_action('wp', [$this, 'removeInheritedLayout'], 20);
        add_filter('body_class', [$this, 'addBodyClass']);
        add_filter('document_title_parts', [$this, 'filterDocumentTitle']);
        add_action('wp_head', [$this, 'renderMetaDescription'], 1);
    }

    public static function isCurrent(): bool
    {
        return is_page(array_keys(self::PAGE_KEYS));
    }

    public static function currentKey(): ?string
    {
        $page = get_queried_object();

        return $page instanceof \WP_Post ? (self::PAGE_KEYS[$page->post_name] ?? null) : null;
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
        }

        return $classes;
    }

    /** @param array<string, string> $parts */
    public function filterDocumentTitle(array $parts): array
    {
        $document = $this->currentDocument();

        if (is_array($document)) {
            $parts['title'] = (string) $document['shortTitle'];
        }

        return $parts;
    }

    public function renderMetaDescription(): void
    {
        $document = $this->currentDocument();

        if (is_array($document)) {
            echo '<meta name="description" content="' . esc_attr((string) $document['description']) . '">' . "\n";
        }
    }

    /** @return array<string, mixed>|null */
    private function currentDocument(): ?array
    {
        $key = self::currentKey();

        return $key === null ? null : (new LegalDocumentRepository())->find($key);
    }
}
