<?php

declare(strict_types=1);

namespace Maruderm\Legal;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the canonical legal page hierarchy from the reviewed legal snapshot. */
final class LegalDocumentRenderer
{
    private LegalDocumentRepository $repository;

    public function __construct(?LegalDocumentRepository $repository = null)
    {
        $this->repository = $repository ?? new LegalDocumentRepository();
    }

    public function render(string $key): void
    {
        $document = $this->repository->find($key);

        if (!is_array($document)) {
            return;
        }

        $sections = is_array($document['sections'] ?? null) ? $document['sections'] : [];
        $alternate = $key === 'publicOffer'
            ? ['slug' => 'terms-and-privacy', 'label' => 'Умови продажу та конфіденційність']
            : ['slug' => 'public-offer', 'label' => 'Публічна оферта'];
        $alternate_page = get_page_by_path($alternate['slug']);
        $alternate_url = $alternate_page instanceof \WP_Post
            ? get_permalink($alternate_page)
            : home_url('/' . $alternate['slug'] . '/');

        echo '<main><section class="legal-hero legal-hero--' . esc_attr((string) $document['slug']) . '" aria-labelledby="legal-page-title"><div class="shell">';
        echo '<nav class="breadcrumbs legal-hero__breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><span>' . esc_html((string) $document['shortTitle']) . '</span></nav>';
        echo '<div class="legal-hero__layout"><div class="legal-hero__content"><span class="kicker">' . esc_html((string) $document['eyebrow']) . '</span><h1 id="legal-page-title">' . esc_html((string) $document['title']) . '</h1>';

        if (!empty($document['subtitle'])) {
            echo '<p class="legal-hero__subtitle">' . esc_html((string) $document['subtitle']) . '</p>';
        }

        echo '<p class="legal-hero__description">' . esc_html((string) $document['description']) . '</p></div>';
        echo '<aside class="legal-hero__summary" aria-label="Коротко про документ"><span class="legal-hero__summary-label">Структура документа</span><strong>' . esc_html((string) count($sections)) . '</strong><span>розділів</span><div class="legal-hero__summary-rule"></div><p>Офіційний текст українською мовою</p></aside></div></div></section>';
        echo '<section class="legal-document" id="legal-document"><div class="shell legal-document__layout"><aside class="legal-document__sidebar"><div class="legal-document__sidebar-inner"><p class="legal-document__nav-label">У цьому документі</p><nav class="legal-document__nav" aria-label="Розділи документа">';

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            echo '<a href="#' . esc_attr((string) $section['id']) . '"><span>' . esc_html(str_pad((string) $section['number'], 2, '0', STR_PAD_LEFT)) . '</span>' . esc_html((string) $section['title']) . '</a>';
        }

        echo '</nav><div class="legal-document__switcher"><span>Інший документ</span><a href="' . esc_url($alternate_url) . '">' . esc_html($alternate['label']) . '<i aria-hidden="true">→</i></a></div></div></aside>';
        echo '<article class="legal-document__content" aria-label="' . esc_attr((string) $document['shortTitle']) . '"><header class="legal-document__content-header"><span>Maruderm Україна</span><p>' . esc_html((string) $document['shortTitle']) . '</p></header>';

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $section_id = (string) $section['id'];
            echo '<section class="legal-section" id="' . esc_attr($section_id) . '" aria-labelledby="' . esc_attr($section_id) . '-title"><header class="legal-section__heading"><span aria-hidden="true">' . esc_html(str_pad((string) $section['number'], 2, '0', STR_PAD_LEFT)) . '</span><h2 id="' . esc_attr($section_id) . '-title">' . esc_html((string) $section['number'] . '. ' . (string) $section['title']) . '</h2></header><div class="legal-section__body">';

            foreach ((array) ($section['blocks'] ?? []) as $block) {
                if (is_array($block)) {
                    $this->renderBlock($block);
                }
            }

            echo '</div></section>';
        }

        echo '<footer class="legal-document__end"><span>Кінець документа</span><a href="#legal-document">До змісту ↑</a></footer></article></div></section></main>';
    }

    /** @param array<string, mixed> $block */
    private function renderBlock(array $block): void
    {
        $type = (string) ($block['type'] ?? '');

        if ($type === 'paragraph') {
            echo '<p>' . esc_html((string) ($block['text'] ?? '')) . '</p>';
            return;
        }

        if ($type === 'list') {
            echo '<ul>';

            foreach ((array) ($block['items'] ?? []) as $item) {
                echo '<li>' . esc_html((string) $item) . '</li>';
            }

            echo '</ul>';
            return;
        }

        if ($type !== 'contact') {
            return;
        }

        echo '<address class="legal-section__contact">';

        foreach ((array) ($block['lines'] ?? []) as $line) {
            if (!is_array($line)) {
                continue;
            }

            echo '<p><span>' . esc_html((string) ($line['label'] ?? '')) . ':</span> <a href="' . esc_url((string) ($line['href'] ?? '')) . '">' . esc_html((string) ($line['value'] ?? '')) . '</a></p>';
        }

        echo '</address>';
    }
}
