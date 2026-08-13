<?php

declare(strict_types=1);

namespace Maruderm\Account;

use Maruderm\WooCommerce\ProductCardRenderer;
use Maruderm\WooCommerce\StockNotificationRenderer;
use Maruderm\WooCommerce\StockNotificationService;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the canonical authenticated dashboard with live WooCommerce data. */
final class AccountRenderer
{
    private ProductCardRenderer $productCards;
    private StockNotificationRenderer $notificationRenderer;
    private StockNotificationService $notificationService;

    public function __construct(
        ?ProductCardRenderer $productCards = null,
        ?StockNotificationRenderer $notificationRenderer = null,
        ?StockNotificationService $notificationService = null
    ) {
        $this->productCards = $productCards ?? new ProductCardRenderer();
        $this->notificationRenderer = $notificationRenderer ?? new StockNotificationRenderer();
        $this->notificationService = $notificationService ?? new StockNotificationService();
    }

    public function render(): void
    {
        $user = wp_get_current_user();

        if (!$user instanceof \WP_User || $user->ID <= 0) {
            return;
        }

        $orders = function_exists('wc_get_orders') ? wc_get_orders([
            'customer_id' => $user->ID,
            'limit' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]) : [];
        $notifications = $this->notificationService->subscriptionsForUser($user->ID);
        $endpoint = function_exists('WC') && WC()->query ? WC()->query->get_current_endpoint() : '';
        $name = $user->first_name !== '' ? $user->first_name : $user->display_name;
        $initials = $this->initials($user);

        echo '<main class="account-page">';
        $this->renderHero($name, $initials);
        echo '<section class="account-content"><div class="shell account-layout">';
        $this->renderSidebar($user, $initials, count($orders), count($notifications), $endpoint === '');
        echo '<div class="account-main">';

        if ($endpoint !== '') {
            echo '<section class="account-panel is-active"><header class="account-panel__heading"><div><span class="kicker">Особистий кабінет</span><h2>' . esc_html(wc_get_account_endpoint_page_title($endpoint)) . '</h2></div></header>';
            do_action('woocommerce_account_content');
            echo '</section>';
        } else {
            $this->renderDashboardPanels($user, $orders, $notifications);
        }

        echo '</div></div></section></main>';
    }

    private function renderHero(string $name, string $initials): void
    {
        echo '<section class="account-hero"><div class="shell"><nav class="breadcrumbs" aria-label="Навігаційний ланцюжок">';
        echo '<a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><span>Особистий кабінет</span></nav>';
        echo '<div class="account-hero__content"><div class="account-hero__person"><span class="account-avatar">' . esc_html($initials) . '<i aria-hidden="true"></i></span><div>';
        echo '<span class="kicker">Твій простір Maruderm</span><h1>Привіт, ' . esc_html($name) . '!</h1><p>Усе про твої замовлення та б’юті-ритуали — в одному місці.</p></div></div>';
        echo '<div class="account-hero__club"><span class="account-hero__club-icon">' . $this->icon('sparkles') . '</span><div>';
        echo '<small>Maruderm Club</small><strong>Твій beauty-простір</strong><span><i style="width: 68%"></i></span><p>Догляд, замовлення та добірки поруч</p></div></div></div></div></section>';
    }

    private function renderSidebar(\WP_User $user, string $initials, int $orderCount, int $notificationCount, bool $tabs): void
    {
        $accountUrl = wc_get_page_permalink('myaccount');
        echo '<aside class="account-sidebar" aria-label="Розділи особистого кабінету"><div class="account-sidebar__identity"><span>' . esc_html($initials) . '</span><div>';
        echo '<strong>' . esc_html($user->display_name) . '</strong><small>' . esc_html($user->user_email) . '</small></div></div><nav class="account-nav" role="tablist" aria-orientation="vertical">';
        $items = [
            ['overview', 'grid', 'Огляд', '', $accountUrl],
            ['orders', 'package', 'Мої замовлення', $orderCount, wc_get_account_endpoint_url('orders')],
            ['profile', 'user', 'Особисті дані', '', wc_get_account_endpoint_url('edit-account')],
            ['wishlist', 'heart', 'Обране', '', $accountUrl],
            ['notifications', 'bell', 'Сповіщення про наявність', $notificationCount, wc_get_account_endpoint_url('stock-notifications')],
            ['recent', 'clock', 'Переглянуті', '', $accountUrl],
        ];

        foreach ($items as [$key, $icon, $label, $count, $url]) {
            echo '<button class="' . ($key === 'overview' ? 'is-active' : '') . '" type="button" role="tab" aria-selected="' . ($key === 'overview' ? 'true' : 'false') . '" data-account-tab="' . esc_attr($key) . '" data-account-url="' . esc_url($url) . '" data-account-tabs="' . ($tabs ? 'yes' : 'no') . '">';
            echo $this->icon($icon) . '<span>' . esc_html($label) . '</span>' . ($count !== '' ? '<i>' . esc_html((string) $count) . '</i>' : '') . '</button>';
        }

        echo '</nav><a class="account-sidebar__logout" href="' . esc_url(wc_logout_url()) . '">' . $this->icon('logout') . 'Вийти з акаунта</a>';
        echo '<div class="account-sidebar__support"><span>' . $this->icon('chat') . '</span><strong>Потрібна допомога?</strong><p>Ми поруч щодня з 09:00 до 20:00</p><a href="' . esc_url(home_url('/kontakty/')) . '">Написати нам →</a></div></aside>';
    }

    /** @param \WC_Order[] $orders @param \WC_Product[] $notifications */
    private function renderDashboardPanels(\WP_User $user, array $orders, array $notifications): void
    {
        $this->renderOverview($orders);
        $this->renderOrders($orders);
        $this->renderProfile($user);
        $this->renderWishlist();
        $this->renderNotifications($user, $notifications);
        $this->renderRecent();
    }

    /** @param \WC_Order[] $orders */
    private function renderOverview(array $orders): void
    {
        echo '<section class="account-panel is-active" role="tabpanel" data-account-panel="overview"><header class="account-panel__heading"><div><span class="kicker">Огляд акаунта</span><h2>Твій beauty-простір</h2></div><span class="account-panel__date">Оновлено сьогодні</span></header>';
        echo '<div class="account-stats"><article><span class="account-stat__icon account-stat__icon--purple">' . $this->icon('package') . '</span><div><small>Замовлення</small><strong>' . esc_html((string) count($orders)) . '</strong><p>Уся історія покупок</p></div><button type="button" data-account-link="orders">→</button></article>';
        echo '<article><span class="account-stat__icon account-stat__icon--pink">' . $this->icon('heart') . '</span><div><small>В обраному</small><strong data-overview-wishlist-count>0</strong><p>Збережені для тебе</p></div><button type="button" data-account-link="wishlist">→</button></article>';
        echo '<article><span class="account-stat__icon account-stat__icon--mint">' . $this->icon('sparkles') . '</span><div><small>Твій статус</small><strong>Maruderm</strong><p>Персональний простір активний</p></div><a href="' . esc_url(home_url('/catalog/')) . '">→</a></article></div>';

        if (isset($orders[0]) && $orders[0] instanceof \WC_Order) {
            $this->renderOrderHighlight($orders[0]);
        }

        echo '<div class="account-overview-grid"><article class="account-routine-card"><span class="account-routine-card__art"></span><div><span class="kicker">Персональний догляд</span><h3>Твоя рутина чекає</h3><p>Пройди коротку діагностику та отримай добірку засобів під потреби волосся.</p><a href="' . esc_url(home_url('/hair-analysis/')) . '">Пройти діагностику' . $this->icon('arrow') . '</a></div></article>';
        echo '<article class="account-profile-note"><span>' . $this->icon('user') . '</span><div><small>Особистий профіль</small><strong>Перевір контактні дані</strong><p>Актуальні дані допомагають швидше оформлювати замовлення.</p><button type="button" data-account-link="profile">Доповнити профіль →</button></div></article></div></section>';
    }

    private function renderOrderHighlight(\WC_Order $order): void
    {
        echo '<article class="account-order-highlight"><div class="account-order-highlight__top"><div><span class="account-live-dot"></span><small>Останнє замовлення</small><h3>№ ' . esc_html($order->get_order_number()) . '</h3></div>';
        echo '<div><small>Статус</small><strong>' . esc_html(wc_get_order_status_name($order->get_status())) . '</strong></div></div>';
        echo '<div class="account-order-highlight__bottom"><div class="account-order-thumbs">' . $this->orderImages($order) . '</div><div><span>' . esc_html((string) $order->get_item_count()) . ' товари</span><strong>' . wp_kses_post($order->get_formatted_order_total()) . '</strong></div>';
        echo '<a class="button button--dark" href="' . esc_url($order->get_view_order_url()) . '">Деталі замовлення' . $this->icon('arrow') . '</a></div></article>';
    }

    /** @param \WC_Order[] $orders */
    private function renderOrders(array $orders): void
    {
        echo '<section class="account-panel" role="tabpanel" data-account-panel="orders" hidden><header class="account-panel__heading"><div><span class="kicker">Історія покупок</span><h2>Мої замовлення</h2><p>Стеж за доставкою, переглядай деталі та повторюй улюблені покупки.</p></div></header>';
        echo '<div class="account-order-filters"><button class="is-active" type="button" data-order-filter="all">Усі <span>' . esc_html((string) count($orders)) . '</span></button></div><div class="account-orders">';

        foreach ($orders as $order) {
            if (!$order instanceof \WC_Order) {
                continue;
            }

            echo '<article class="account-order"><div class="account-order__summary"><div class="account-order__number"><small>Замовлення</small><strong>№ ' . esc_html($order->get_order_number()) . '</strong><span>' . esc_html(wc_format_datetime($order->get_date_created())) . '</span></div>';
            echo '<span class="account-order__status account-order__status--active"><i></i>' . esc_html(wc_get_order_status_name($order->get_status())) . '</span>';
            echo '<div class="account-order__products">' . $this->orderImages($order) . '</div><div class="account-order__total"><small>Сума</small><strong>' . wp_kses_post($order->get_formatted_order_total()) . '</strong></div>';
            echo '<a href="' . esc_url($order->get_view_order_url()) . '" aria-label="Деталі замовлення">' . $this->icon('arrow') . '</a></div></article>';
        }

        echo '</div><div class="account-empty"' . ($orders !== [] ? ' hidden' : '') . '><span>' . $this->icon('package') . '</span><h3>Тут поки порожньо</h3><p>Після покупки замовлення з’являться тут.</p></div></section>';
    }

    private function renderProfile(\WP_User $user): void
    {
        echo '<section class="account-panel" role="tabpanel" data-account-panel="profile" hidden><header class="account-panel__heading account-panel__heading--action"><div><span class="kicker">Твої дані</span><h2>Особиста інформація</h2><p>Керуй контактами для швидшого оформлення.</p></div><button class="account-edit-button" type="button" data-profile-edit>' . $this->icon('edit') . '<span>Редагувати</span></button></header>';
        echo '<form class="account-profile-form" method="post" data-profile-form><section class="account-form-card"><div class="account-form-card__heading"><span>' . $this->icon('user') . '</span><div><small>Основне</small><h3>Контактні дані</h3></div></div><div class="account-fields">';
        $fields = [
            ['Ім’я', 'account_first_name', $user->first_name, 'text'],
            ['Прізвище', 'account_last_name', $user->last_name, 'text'],
            ['Ім’я для відображення', 'account_display_name', $user->display_name, 'text'],
            ['Email', 'account_email', $user->user_email, 'email'],
        ];

        foreach ($fields as [$label, $name, $value, $type]) {
            echo '<label><span>' . esc_html($label) . '</span><input name="' . esc_attr($name) . '" type="' . esc_attr($type) . '" value="' . esc_attr($value) . '" disabled required></label>';
        }

        echo '</div></section><div class="account-profile-actions" hidden data-profile-actions><button type="button" data-profile-cancel>Скасувати</button><button class="button button--dark" type="submit" name="save_account_details" value="Зберегти зміни">Зберегти зміни</button></div>';
        wp_nonce_field('save_account_details', 'save-account-details-nonce');
        echo '<input type="hidden" name="action" value="save_account_details"><p class="account-form-status" data-profile-status aria-live="polite"></p></form></section>';
    }

    private function renderWishlist(): void
    {
        echo '<section class="account-panel" role="tabpanel" data-account-panel="wishlist" hidden><header class="account-panel__heading"><div><span class="kicker">Збережене</span><h2>Обрані товари</h2><p>Твоя персональна полиця засобів, до яких хочеться повернутися.</p></div><span class="account-panel__count"><b data-wishlist-heading-count>0</b> товарів</span></header>';
        echo '<div class="product-grid account-product-grid" data-wishlist-grid></div><div class="account-empty" data-wishlist-empty><span>' . $this->icon('heart') . '</span><h3>Список обраного порожній</h3><p>Зберігай товари серцем, щоб легко знайти їх пізніше.</p><a class="button button--dark" href="' . esc_url(wc_get_page_permalink('shop')) . '">Перейти до каталогу</a></div></section>';
    }

    /** @param \WC_Product[] $products */
    private function renderNotifications(\WP_User $user, array $products): void
    {
        echo '<section class="account-panel" role="tabpanel" data-account-panel="notifications" hidden><header class="account-panel__heading"><div><span class="kicker">Стежимо за товарами</span><h2>Сповіщення про наявність</h2><p>Ми повідомимо на email, щойно обраний товар знову буде доступний.</p></div><span class="account-panel__count"><b data-notification-heading-count>' . esc_html((string) count($products)) . '</b> активні</span></header>';
        echo $this->notificationRenderer->accountPanel($products, $user->user_email); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</section>';
    }

    private function renderRecent(): void
    {
        $ids = isset($_COOKIE['woocommerce_recently_viewed'])
            ? array_values(array_filter(array_map('absint', explode('|', wp_unslash((string) $_COOKIE['woocommerce_recently_viewed'])))))
            : [];
        echo '<section class="account-panel" role="tabpanel" data-account-panel="recent" hidden><header class="account-panel__heading"><div><span class="kicker">Твоя історія</span><h2>Нещодавно переглянуті</h2><p>Продовжуй вибір із того місця, де зупинилася.</p></div></header><div class="product-grid account-product-grid">';

        foreach (array_slice(array_reverse($ids), 0, 8) as $productId) {
            $product = wc_get_product($productId);

            if ($product instanceof \WC_Product && $product->get_status() === 'publish') {
                echo $this->productCards->render($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }

        echo '</div><div class="account-empty"' . ($ids !== [] ? ' hidden' : '') . '><span>' . $this->icon('clock') . '</span><h3>Історія переглядів порожня</h3><p>Товари, які ти відкриватимеш, з’являться тут.</p><a class="button button--dark" href="' . esc_url(wc_get_page_permalink('shop')) . '">Відкрити каталог</a></div></section>';
    }

    private function orderImages(\WC_Order $order): string
    {
        $html = '';

        foreach (array_slice($order->get_items(), 0, 3) as $item) {
            $product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : false;

            if ($product instanceof \WC_Product) {
                $html .= '<a href="' . esc_url($product->get_permalink()) . '">' . wp_kses_post($product->get_image('woocommerce_thumbnail')) . '</a>';
            }
        }

        return $html;
    }

    private function initials(\WP_User $user): string
    {
        $parts = array_values(array_filter([$user->first_name, $user->last_name]));
        $source = $parts !== [] ? implode(' ', $parts) : $user->display_name;
        $words = preg_split('/\s+/u', trim($source)) ?: [];
        $letters = array_map(static fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 2));

        return mb_strtoupper(implode('', $letters));
    }

    private function icon(string $name): string
    {
        $paths = [
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"></path>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>',
            'package' => '<path d="m4 7 8-4 8 4-8 4-8-4Z"></path><path d="M4 7v10l8 4 8-4V7M12 11v10"></path>',
            'user' => '<circle cx="12" cy="7" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>',
            'heart' => '<path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"></path>',
            'bell' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"></path><path d="M10 21h4"></path>',
            'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
            'logout' => '<path d="M10 4H5v16h5M14 8l4 4-4 4M8 12h10"></path>',
            'chat' => '<path d="M4 5h16v11H9l-5 4V5Z"></path>',
            'sparkles' => '<path d="m12 3 1.5 4.2L18 9l-4.5 1.8L12 15l-1.5-4.2L6 9l4.5-1.8L12 3Z"></path>',
            'edit' => '<path d="m4 16-1 5 5-1L20 8l-4-4L4 16Z"></path><path d="m14 6 4 4"></path>',
        ];

        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
    }
}
