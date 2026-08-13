<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Renders stock notification controls without owning persistence or request handling.
 */
final class StockNotificationRenderer
{
    private StockNotificationService $service;

    public function __construct(?StockNotificationService $service = null)
    {
        $this->service = $service ?? new StockNotificationService();
    }

    public function cardButton(\WC_Product $product): string
    {
        $active = is_user_logged_in()
            && $this->service->isSubscribed($product->get_id(), get_current_user_id());
        $label = $active
            ? 'Сповіщення про наявність увімкнено'
            : sprintf('Повідомити, коли %s з’явиться в наявності', $product->get_name());

        return sprintf(
            '<button class="product-card__notify%1$s" type="button" data-stock-notify="%2$d" aria-label="%3$s" aria-pressed="%4$s" title="Повідомити про наявність">%5$s</button>',
            $active ? ' is-active' : '',
            $product->get_id(),
            esc_attr($label),
            $active ? 'true' : 'false',
            $this->bellIcon()
        );
    }

    public function singleButton(\WC_Product $product): string
    {
        $active = is_user_logged_in()
            && $this->service->isSubscribed($product->get_id(), get_current_user_id());
        $hidden = $product->is_type('variable') && $product->is_in_stock();

        return sprintf(
            '<button class="product-buy__notify%1$s" type="button" data-product-stock-notify data-stock-notify="%2$d" data-parent-product-id="%2$d" aria-pressed="%3$s"%4$s><span><small>Немає в наявності</small><strong data-stock-notify-label>%5$s</strong></span>%6$s</button>',
            $active ? ' is-active' : '',
            $product->get_id(),
            $active ? 'true' : 'false',
            $hidden ? ' hidden' : '',
            esc_html($active ? 'Сповіщення увімкнено' : 'Повідомити, коли з’явиться'),
            $this->bellIcon()
        );
    }

    /** @param \WC_Product[] $products */
    public function accountPanel(array $products, string $email): string
    {
        $rows = '';

        foreach ($products as $product) {
            $rows .= $this->accountRow($product, $email);
        }

        $is_empty = $rows === '';

        return '<section class="maruderm-stock-notifications">'
            . '<header class="maruderm-stock-notifications__header"><span>Персональні оновлення</span><h2>Сповіщення про наявність</h2><p>Ми надішлемо лист, щойно обраний товар знову можна буде замовити.</p></header>'
            . '<div class="account-notifications" data-account-notifications' . ($is_empty ? ' hidden' : '') . '>' . $rows . '</div>'
            . '<div class="account-notifications-empty" data-account-notifications-empty' . (!$is_empty ? ' hidden' : '') . '>'
            . $this->bellIcon()
            . '<h3>Активних сповіщень немає</h3>'
            . '<p>Натисни дзвіночок біля відсутнього товару — і ми додамо його сюди.</p>'
            . '<a href="' . esc_url(wc_get_page_permalink('shop')) . '">Перейти до каталогу</a>'
            . '</div></section>';
    }

    private function accountRow(\WC_Product $product, string $email): string
    {
        $category_product_id = $product->get_parent_id() ?: $product->get_id();
        $categories = wc_get_product_category_list($category_product_id, ', ', '', '');
        $category = trim(wp_strip_all_tags($categories));
        $category = $category !== '' ? $category : 'Maruderm';
        $permalink = $product->get_permalink();

        return sprintf(
            '<article class="account-notification" data-notification-product="%1$d">'
            . '<a class="account-notification__image" href="%2$s">%3$s</a>'
            . '<div class="account-notification__copy"><span>%4$s</span><h3><a href="%2$s">%5$s</a></h3><p><i></i> Очікуємо повернення</p></div>'
            . '<div class="account-notification__delivery"><span>%6$s</span><div><small>Спосіб сповіщення</small><strong>Email · %7$s</strong></div></div>'
            . '<button type="button" data-stock-notify="%1$d" aria-pressed="true" aria-label="Вимкнути сповіщення для %5$s">%8$s<span>Вимкнути</span></button>'
            . '</article>',
            $product->get_id(),
            esc_url($permalink),
            wp_kses_post($product->get_image('woocommerce_thumbnail', ['loading' => 'lazy'])),
            esc_html($category),
            esc_html($product->get_name()),
            $this->mailIcon(),
            esc_html($email),
            $this->closeIcon()
        );
    }

    private function bellIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"></path><path d="M10 21h4"></path></svg>';
    }

    private function mailIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>';
    }

    private function closeIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"></path></svg>';
    }
}
