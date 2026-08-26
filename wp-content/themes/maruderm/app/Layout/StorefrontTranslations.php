<?php

declare(strict_types=1);

namespace Maruderm\Layout;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Translates inherited parent-theme storefront copy that is stored as legacy English settings. */
final class StorefrontTranslations implements Registrable
{
    use Loadable;

    /** @var array<string, array<string, string>> */
    private const OPTION_TRANSLATIONS = [
        'footer_recently_viewed_title' => [
            'Your Recently Viewed Products' => 'Нещодавно переглянуті товари',
        ],
        'footer_recently_viewed_link_text' => [
            'View All' => 'Переглянути всі',
        ],
        'header_recently_viewed_title' => [
            'Your Recently Viewed' => 'Нещодавно переглянуті вами',
        ],
        'header_recently_viewed_link_text' => [
            'View All' => 'Переглянути всі',
        ],
    ];

    private const MARTFURY_TEXT_TRANSLATIONS = [
        'Dashboard' => 'Особистий кабінет',
        'Account Settings' => 'Особисті дані',
        'Orders History' => 'Історія замовлень',
        'Recently Viewed Products is a function which helps you keep track of your recent viewing history.'
            => 'Нещодавно переглянуті товари допомагають відстежувати історію ваших переглядів.',
        'Shop Now' => 'Перейти до каталогу',
    ];

    public function register(): void
    {
        add_filter('martfury_get_option', [$this, 'translateLegacyOption'], 20, 2);
        add_filter('gettext', [$this, 'translateLegacyText'], 20, 3);
    }

    public function translateLegacyOption(mixed $value, string $name): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return self::OPTION_TRANSLATIONS[$name][$value] ?? $value;
    }

    public function translateLegacyText(string $translation, string $text, string $domain): string
    {
        if ($domain !== 'martfury') {
            return $translation;
        }

        return self::MARTFURY_TEXT_TRANSLATIONS[$text] ?? $translation;
    }
}
