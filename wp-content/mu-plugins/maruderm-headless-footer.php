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
        'args' => ['language' => ['type' => 'String']],
        'resolve' => static function ($root, array $args) {
            return maruderm_resolve_footer_graphql((string) ($args['language'] ?? 'uk'));
        },
    ]);
}

function maruderm_resolve_footer_graphql(string $language = 'uk'): array
{
    $catalog = new \Maruderm\LandingPage\LandingPageCatalog();
    $taxonomyResolver = new \Maruderm\Multilingual\TaxonomyPresentationResolver();
    $russian = $language === 'ru';

    return [
        'tagline' => 'nature embraces science',
        'description' => $russian
            ? 'Действенные формулы для ежедневного ухода. Понятно, красиво и без лишнего.'
            : 'Дієві формули для щоденного догляду. Зрозуміло, красиво й без зайвого.',
        'catalogHeading' => $russian ? 'Каталог' : 'Каталог',
        'catalogLinks' => array_map(static function (\WP_Term $category) use ($catalog, $taxonomyResolver, $language): array {
            $localized = $taxonomyResolver->translateTerm($category, $language);
            $url = $language === 'ru'
                ? home_url('/ru/catalog/' . $localized->slug . '/')
                : $catalog->categoryUrl($category);

            return ['label' => $localized->name, 'url' => $url];
        }, $catalog->categories(4)),
        'helpHeading' => $russian ? 'Помощь' : 'Допомога',
        'helpLinks' => maruderm_footer_help_links($language),
        'subscribeHeading' => $russian ? 'Новости без шума' : 'Новини без шуму',
        'subscribeDescription' => $russian
            ? 'Новинки, советы и специальные предложения — только полезное.'
            : 'Новинки, поради й спеціальні пропозиції — тільки корисне.',
        'legalLinks' => $russian
            ? [
                ['label' => 'Политика конфиденциальности', 'url' => maruderm_footer_page_url(['terms-and-privacy'])],
                ['label' => 'Публичная оферта', 'url' => maruderm_footer_page_url(['public-offer'])],
            ]
            : [
                ['label' => 'Політика конфіденційності', 'url' => maruderm_footer_page_url(['terms-and-privacy'])],
                ['label' => 'Публічна оферта', 'url' => maruderm_footer_page_url(['public-offer'])],
            ],
        'copyrightText' => $russian ? 'Maruderm Украина' : 'Maruderm Україна',
        'copyrightYear' => (int) wp_date('Y'),
    ];
}

/** @return array<int, array<string, string>> */
function maruderm_footer_help_links(string $language = 'uk'): array
{
    $links = $language === 'ru'
        ? [
            'Доставка и оплата' => ['dostavka-i-oplata', 'delivery-payment'],
            'Возврат' => ['povernennya', 'returns'],
            'Контакты' => ['kontakty', 'contacts'],
            'FAQ' => ['faq'],
        ]
        : [
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
