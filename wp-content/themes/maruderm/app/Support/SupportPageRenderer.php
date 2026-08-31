<?php

declare(strict_types=1);

namespace Maruderm\Support;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders support content with the established Maruderm document presentation. */
final class SupportPageRenderer
{
    private SupportPageRepository $repository;

    public function __construct(?SupportPageRepository $repository = null)
    {
        $this->repository = $repository ?? new SupportPageRepository();
    }

    public function render(string $slug): void
    {
        $page = $this->repository->find($slug);

        if (!is_array($page)) {
            return;
        }

        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        echo '<main><section class="legal-hero legal-hero--' . esc_attr($slug) . '" aria-labelledby="support-page-title"><div class="shell">';
        echo '<nav class="breadcrumbs legal-hero__breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><span>' . esc_html((string) $page['shortTitle']) . '</span></nav>';
        echo '<div class="legal-hero__layout"><div class="legal-hero__content"><span class="kicker">' . esc_html((string) $page['eyebrow']) . '</span><h1 id="support-page-title">' . esc_html((string) $page['title']) . '</h1>';
        echo '<p class="legal-hero__description">' . esc_html((string) $page['description']) . '</p></div>';
        echo '<aside class="legal-hero__summary" aria-label="Коротко про сторінку"><span class="legal-hero__summary-label">Корисна інформація</span><strong>' . esc_html((string) count($sections)) . '</strong><span>розділів</span><div class="legal-hero__summary-rule"></div><p>Актуально для замовлень в Україні</p></aside></div></div></section>';
        echo '<section class="legal-document" id="support-content"><div class="shell legal-document__layout"><aside class="legal-document__sidebar"><div class="legal-document__sidebar-inner"><p class="legal-document__nav-label">На цій сторінці</p><nav class="legal-document__nav" aria-label="Розділи сторінки">';

        foreach ($sections as $index => $section) {
            $number = $index + 1;
            echo '<a href="#support-section-' . esc_attr((string) $number) . '"><span>' . esc_html(str_pad((string) $number, 2, '0', STR_PAD_LEFT)) . '</span>' . esc_html((string) ($section['title'] ?? '')) . '</a>';
        }

        echo '</nav></div></aside><article class="legal-document__content" aria-label="' . esc_attr((string) $page['shortTitle']) . '"><header class="legal-document__content-header"><span>Maruderm Україна</span><p>' . esc_html((string) $page['shortTitle']) . '</p></header>';

        foreach ($sections as $index => $section) {
            $number = $index + 1;
            $section_id = 'support-section-' . $number;
            echo '<section class="legal-section" id="' . esc_attr($section_id) . '" aria-labelledby="' . esc_attr($section_id) . '-title"><header class="legal-section__heading"><span aria-hidden="true">' . esc_html(str_pad((string) $number, 2, '0', STR_PAD_LEFT)) . '</span><h2 id="' . esc_attr($section_id) . '-title">' . esc_html((string) $section['title']) . '</h2></header><div class="legal-section__body">';

            foreach ((array) ($section['blocks'] ?? []) as $block) {
                if (is_array($block)) {
                    $this->renderBlock($block);
                }
            }

            echo '</div></section>';
        }

        echo '<footer class="legal-document__end"><span>Кінець сторінки</span><a href="#support-content">До змісту ↑</a></footer></article></div></section></main>';
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
            if (is_array($line)) {
                echo '<p><span>' . esc_html((string) ($line['label'] ?? '')) . ':</span> <a href="' . esc_url((string) ($line['href'] ?? '')) . '">' . esc_html((string) ($line['value'] ?? '')) . '</a></p>';
            }
        }
        echo '</address>';
    }
}
