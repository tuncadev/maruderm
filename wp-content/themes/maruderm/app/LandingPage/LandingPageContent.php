<?php

declare(strict_types=1);

namespace Maruderm\LandingPage;

if (!defined('ABSPATH')) {
    exit();
}

final class LandingPageContent
{
    public function hero(): array
    {
        return [
            'eyebrow' => 'Косметика нового покоління',
            'title' => 'Догляд, що працює <em>у твоєму ритмі.</em>',
            'description' => 'Активні формули для щоденних ритуалів — зрозуміло, красиво й без зайвого.',
            'primary_label' => 'Знайти свій догляд',
            'secondary_label' => 'Дивитися новинки',
        ];
    }

    public function promises(): array
    {
        return [
            ['number' => '01', 'title' => 'Активні формули', 'text' => 'Продумані поєднання компонентів для помітного результату.'],
            ['number' => '02', 'title' => 'Чесний догляд', 'text' => 'Зрозумілі засоби без складних та непотрібних ритуалів.'],
            ['number' => '03', 'title' => 'Твоя рутина', 'text' => 'Рішення для різних типів шкіри, волосся та способу життя.'],
        ];
    }

    public function routines(): array
    {
        return [
            ['tone' => 'aqua', 'step' => '01', 'title' => 'М’яке очищення', 'text' => 'Почни з чистої основи та комфортного відчуття після вмивання.'],
            ['tone' => 'lilac', 'step' => '02', 'title' => 'Цільова дія', 'text' => 'Додай сироватку або тонік під актуальні потреби шкіри.'],
            ['tone' => 'peach', 'step' => '03', 'title' => 'Захист щодня', 'text' => 'Завершуй рутину зволоженням і SPF незалежно від сезону.'],
        ];
    }

    public function benefits(): array
    {
        return [
            ['icon' => 'spark', 'title' => 'Оригінальна продукція', 'text' => 'Пряме постачання Maruderm'],
            ['icon' => 'box', 'title' => 'Швидке відправлення', 'text' => 'Дбайливо пакуємо замовлення'],
            ['icon' => 'shield', 'title' => 'Безпечна оплата', 'text' => 'Захищений процес покупки'],
            ['icon' => 'heart', 'title' => 'Турботлива підтримка', 'text' => 'Допоможемо обрати засоби'],
        ];
    }
}
