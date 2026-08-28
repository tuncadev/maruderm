<?php

declare(strict_types=1);

namespace Maruderm\Campaign;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns canonical campaign variants and their live WordPress route mapping. */
final class CampaignPopupContent
{
    /** @var array<string, array<string, mixed>> */
    private const CAMPAIGNS = [
        'home' => [
            'id' => 'ukraine-launch-home',
            'tone' => 'violet',
            'delay' => 4300,
            'eyebrow' => 'WE ARE ONLINE',
            'title' => 'Maruderm is now available in Ukraine.',
            'description' => 'Після 43 країн світу Maruderm офіційно онлайн в Україні. Підпишись на стартові пропозиції, новинки та зрозумілий догляд без зайвого шуму.',
            'stat' => '43',
            'stat_label' => 'країни до України',
            'media_note' => 'Nature embraces science — тепер ближче.',
            'benefits' => ['-10% на знайомство', 'Тільки корисні листи', 'Ранній доступ'],
            'submit_label' => 'Приєднатися',
            'success_message' => 'Ти з нами! Стартова пропозиція вже прямує на email.',
            'video' => 'section-clean-skin.webm',
        ],
        'catalog' => [
            'id' => 'ukraine-launch-catalog',
            'tone' => 'pink',
            'delay' => 4300,
            'eyebrow' => '43 COUNTRIES. NOW UKRAINE.',
            'title' => 'Почни знайомство з Maruderm із -10%.',
            'description' => 'Обирай формули для шкіри, волосся й тіла, а ми надішлемо welcome-код і першими покажемо сезонні добірки та sale drops.',
            'stat' => '149+',
            'stat_label' => 'формул уже онлайн',
            'media_note' => 'Знайди свій активний ритуал.',
            'benefits' => ['Welcome-код', 'Sale alerts', 'Добірки за потребою'],
            'submit_label' => 'Отримати код',
            'success_message' => 'Готово! Перевір email — твій welcome-код уже там.',
            'video' => 'popup-face-cream.webm',
        ],
        'product' => [
            'id' => 'ukraine-launch-product',
            'tone' => 'purple',
            'delay' => 4300,
            'eyebrow' => 'GLOBAL FORMULAS. LOCAL DELIVERY.',
            'title' => 'Світові формули — тепер поруч.',
            'description' => 'Залиш email, щоб першою дізнаватися про нові активи, повернення бестселерів і спеціальні ціни на доглядові ритуали.',
            'stat' => '24h',
            'stat_label' => 'до раннього анонсу',
            'media_note' => 'Science-led care for real life.',
            'benefits' => ['Нові формули', 'Back in stock', 'Ритуали зі знижкою'],
            'submit_label' => 'Бути першою',
            'success_message' => 'Підписку активовано. Наступний beauty drop не пройде повз.',
            'video' => 'popup-retinol.webm',
        ],
        'cart' => [
            'id' => 'ukraine-launch-cart',
            'tone' => 'mint',
            'delay' => 4300,
            'eyebrow' => 'A LITTLE WELCOME GIFT',
            'title' => 'Твій ритуал заслуговує на приємний бонус.',
            'description' => 'Підпишись перед оформленням і отримуй персональні пропозиції, закриті розпродажі та нагадування про улюблені формули.',
            'stat' => '-10%',
            'stat_label' => 'на перше знайомство',
            'media_note' => 'More care. Less noise.',
            'benefits' => ['Закриті пропозиції', 'Без спаму', 'Відписка в один клік'],
            'submit_label' => 'Забрати бонус',
            'success_message' => 'Бонус зарезервовано. Деталі надіслано на твій email.',
            'video' => 'popup-hair-mask.webm',
        ],
        'checkout' => [
            'id' => 'ukraine-launch-checkout',
            'tone' => 'sun',
            'delay' => 4300,
            'eyebrow' => "YOU'RE IN EARLY",
            'title' => 'Українська історія Maruderm тільки починається.',
            'description' => 'Приєднуйся до launch-листів: новинки, сезонні пропозиції та чесні підказки про догляд — коротко й у правильний момент.',
            'stat' => 'UA',
            'stat_label' => 'офіційний онлайн-запуск',
            'media_note' => 'Welcome to the Maruderm community.',
            'benefits' => ['Launch news', 'Подарунки до замовлень', 'Поради без складного'],
            'submit_label' => 'Долучитися',
            'success_message' => 'Welcome! Тепер ти серед перших у Maruderm Ukraine.',
            'video' => 'popup-kids-sun.webm',
        ],
    ];

    public static function currentKey(): ?string
    {
        if (is_page_template('template-coming-soon-page.php')) {
            return null;
        }

        if (is_front_page()) {
            return 'home';
        }

        if (function_exists('is_shop') && (is_shop() || is_product_taxonomy() || (is_search() && get_query_var('post_type') === 'product'))) {
            return 'catalog';
        }

        if (function_exists('is_product') && is_product()) {
            return 'product';
        }

        if (function_exists('is_cart') && is_cart()) {
            return 'cart';
        }

        $step = isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : 'delivery';

        if (function_exists('is_checkout') && is_checkout() && !is_order_received_page() && $step !== 'payment') {
            return 'checkout';
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public static function current(): ?array
    {
        $key = self::currentKey();

        return $key === null ? null : self::CAMPAIGNS[$key];
    }

    /** @return array<string, mixed>|null */
    public static function byId(string $campaign_id): ?array
    {
        foreach (self::CAMPAIGNS as $campaign) {
            if ($campaign['id'] === $campaign_id) {
                return $campaign;
            }
        }

        return null;
    }
}
