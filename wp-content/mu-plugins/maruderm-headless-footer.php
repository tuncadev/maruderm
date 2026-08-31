<?php
/**
 * Exposes the maruderm.dev footer through WPGraphQL for the headless Next.js
 * frontend. Mirrors Maruderm\Layout\FooterRenderer field-for-field (same
 * catalog links via LandingPageCatalog, same help/legal page-slug fallback
 * logic) so the headless footer always matches the live PHP-rendered one.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', 'maruderm_register_footer_graphql');

function maruderm_register_footer_graphql(): void
{
    register_graphql_object_type('MarudermFooterLink', [
        'description' => 'A single footer navigation link.',
        'fields' => [
            'label' => ['type' => 'String'],
            'url' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermFooter', [
        'description' => 'Resolved footer content for the headless frontend.',
        'fields' => [
            'tagline' => ['type' => 'String'],
            'description' => ['type' => 'String'],
            'catalogHeading' => ['type' => 'String'],
            'catalogLinks' => ['type' => ['list_of' => 'MarudermFooterLink']],
            'helpHeading' => ['type' => 'String'],
            'helpLinks' => ['type' => ['list_of' => 'MarudermFooterLink']],
            'subscribeHeading' => ['type' => 'String'],
            'subscribeDescription' => ['type' => 'String'],
            'legalLinks' => ['type' => ['list_of' => 'MarudermFooterLink']],
            'copyrightText' => ['type' => 'String'],
            'copyrightYear' => ['type' => 'Int'],
        ],
    ]);

    register_graphql_field('RootQuery', 'marudermFooter', [
        'type' => 'MarudermFooter',
        'description' => 'Resolved footer content for the headless frontend.',
        'resolve' => static function () {
            return maruderm_resolve_footer_graphql();
        },
    ]);
}

function maruderm_resolve_footer_graphql(): array
{
    $catalog = new \Maruderm\LandingPage\LandingPageCatalog();

    return [
        'tagline' => 'nature embraces science',
        'description' => 'Дієві формули для щоденного догляду. Зрозуміло, красиво й без зайвого.',
        'catalogHeading' => 'Каталог',
        'catalogLinks' => array_map(static function (\WP_Term $category) use ($catalog): array {
            return ['label' => $category->name, 'url' => $catalog->categoryUrl($category)];
        }, $catalog->categories(4)),
        'helpHeading' => 'Допомога',
        'helpLinks' => maruderm_footer_help_links(),
        'subscribeHeading' => 'Новини без шуму',
        'subscribeDescription' => 'Новинки, поради й спеціальні пропозиції — тільки корисне.',
        'legalLinks' => [
            ['label' => 'Політика конфіденційності', 'url' => maruderm_footer_page_url(['terms-and-privacy'])],
            ['label' => 'Публічна оферта', 'url' => maruderm_footer_page_url(['public-offer'])],
        ],
        'copyrightText' => 'Maruderm Україна',
        'copyrightYear' => (int) wp_date('Y'),
    ];
}

/** @return array<int, array<string, string>> */
function maruderm_footer_help_links(): array
{
    $links = [
        'Доставка й оплата' => ['dostavka-i-oplata', 'delivery-payment'],
        'Повернення' => ['povernennya', 'returns'],
        'Контакти' => ['kontakty', 'contacts'],
        'FAQ' => ['faq'],
    ];

    $resolved = [];

    foreach ($links as $label => $slugs) {
        $resolved[] = ['label' => $label, 'url' => maruderm_footer_page_url($slugs)];
    }

    return $resolved;
}

/** @param string[] $slugs */
function maruderm_footer_page_url(array $slugs): string
{
    foreach ($slugs as $slug) {
        $page = get_page_by_path($slug);

        if ($page instanceof \WP_Post) {
            return get_permalink($page);
        }
    }

    return home_url('/');
}
