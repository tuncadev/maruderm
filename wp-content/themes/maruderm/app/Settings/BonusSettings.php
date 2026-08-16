<?php

declare(strict_types=1);

namespace Maruderm\Settings;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Configures loyalty tiers and provides a focused WooCommerce coupon workspace. */
final class BonusSettings implements Registrable
{
    use Loadable;

    public const OPTION_NAME = 'maruderm_bonus_settings';
    public const PAGE_SLUG = 'maruderm-coupons-bonuses';

    private const SETTINGS_GROUP = 'maruderm_bonus_settings_group';
    private const CREATE_COUPON_ACTION = 'maruderm_create_coupon';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_' . self::CREATE_COUPON_ACTION, [$this, 'createCoupon']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            ThemeSettings::PAGE_SLUG,
            'Coupons & Bonuses',
            'Coupons & Bonuses',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(self::SETTINGS_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => $this->defaults(),
        ]);
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== ThemeSettings::PAGE_SLUG . '_page_' . self::PAGE_SLUG) {
            return;
        }

        foreach (['homepage-settings.css', 'bonuses-settings.css'] as $file) {
            $path = get_theme_file_path('assets/admin/' . $file);
            wp_enqueue_style(
                'maruderm-' . str_replace('.css', '', $file),
                get_theme_file_uri('assets/admin/' . $file),
                [],
                file_exists($path) ? (string) filemtime($path) : null
            );
        }
    }

    /** @return array{points_per_uah: float, tiers: array<string, array{name: string, threshold: int}>} */
    public function all(): array
    {
        $saved = get_option(self::OPTION_NAME, []);

        return $this->sanitize(is_array($saved) ? $saved : []);
    }

    public function sanitize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $defaults = $this->defaults();
        $tiers = is_array($input['tiers'] ?? null) ? $input['tiers'] : [];
        $sanitized = [
            'points_per_uah' => max(0.01, min(1000, (float) ($input['points_per_uah'] ?? $defaults['points_per_uah']))),
            'tiers' => [],
        ];
        $previousThreshold = 0;

        foreach ($defaults['tiers'] as $key => $defaultTier) {
            $tier = is_array($tiers[$key] ?? null) ? $tiers[$key] : [];
            $threshold = $key === 'member'
                ? 0
                : max($previousThreshold + 1, absint($tier['threshold'] ?? $defaultTier['threshold']));
            $sanitized['tiers'][$key] = [
                'name' => sanitize_text_field((string) ($tier['name'] ?? $defaultTier['name'])) ?: $defaultTier['name'],
                'threshold' => $threshold,
            ];
            $previousThreshold = $threshold;
        }

        return $sanitized;
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->all();
        echo '<div class="wrap maruderm-settings maruderm-bonuses-settings">';
        echo '<header class="maruderm-settings__header"><div><span>Maruderm Settings</span><h1>Coupons &amp; Bonuses</h1><p>Керуйте правилами Maruderm Club і створюйте реальні купони WooCommerce з одного робочого простору.</p></div><span class="maruderm-settings__status">WooCommerce connected</span></header>';
        $this->renderNotice();
        echo '<div class="maruderm-settings__sections">';
        echo '<form method="post" action="options.php" class="maruderm-settings__card">';
        settings_fields(self::SETTINGS_GROUP);
        echo '<div class="maruderm-settings__card-heading"><span>Bonus logic</span><h2>Maruderm Club tiers</h2><p>Бонуси нараховуються з оплачених товарів після знижок, без доставки, з відніманням повернень.</p></div>';
        $this->renderNumberField('points_per_uah', 'Бонусів за 1 ₴', (float) $settings['points_per_uah'], 0.01, '0.01');
        echo '<div class="maruderm-bonuses-settings__tiers">';

        foreach ($settings['tiers'] as $key => $tier) {
            echo '<fieldset class="maruderm-bonuses-settings__tier"><legend>' . esc_html(ucfirst($key)) . '</legend>';
            $this->renderTierField($key, 'name', 'Назва рівня', (string) $tier['name']);
            $this->renderTierField($key, 'threshold', 'Поріг бонусів', (string) $tier['threshold'], $key === 'member');
            echo '</fieldset>';
        }

        echo '</div><div class="maruderm-settings__actions"><p>Нові правила застосовуються до історії оплачених замовлень одразу після збереження.</p>';
        submit_button('Save Bonus Settings', 'primary', 'submit', false);
        echo '</div></form>';
        $this->renderCouponCreator();
        echo '</div>';
        $this->renderCouponList();
        echo '</div>';
    }

    public function createCoupon(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to create coupons.', 'maruderm'));
        }

        check_admin_referer(self::CREATE_COUPON_ACTION);
        $redirect = admin_url('admin.php?page=' . self::PAGE_SLUG);
        $code = wc_format_coupon_code(wp_unslash((string) ($_POST['coupon_code'] ?? '')));

        if ($code === '' || wc_get_coupon_id_by_code($code) > 0) {
            wp_safe_redirect(add_query_arg('coupon_status', $code === '' ? 'missing' : 'duplicate', $redirect));
            exit();
        }

        $type = sanitize_key(wp_unslash((string) ($_POST['discount_type'] ?? 'percent')));
        $type = in_array($type, ['percent', 'fixed_cart', 'fixed_product'], true) ? $type : 'percent';
        $amount = max(0, (float) wc_format_decimal(wp_unslash((string) ($_POST['coupon_amount'] ?? '0'))));
        $amount = $type === 'percent' ? min(100, $amount) : $amount;
        $coupon = new \WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type($type);
        $coupon->set_amount($amount);
        $coupon->set_description(sanitize_textarea_field(wp_unslash((string) ($_POST['description'] ?? ''))));
        $coupon->set_individual_use(isset($_POST['individual_use']));
        $coupon->set_free_shipping(isset($_POST['free_shipping']));
        $coupon->set_usage_limit(max(0, absint($_POST['usage_limit'] ?? 0)));
        $expiry = sanitize_text_field(wp_unslash((string) ($_POST['date_expires'] ?? '')));

        if ($expiry !== '') {
            $coupon->set_date_expires($expiry);
        }

        $couponId = $coupon->save();
        wp_safe_redirect(add_query_arg([
            'coupon_status' => 'created',
            'coupon_id' => $couponId,
        ], $redirect));
        exit();
    }

    private function renderCouponCreator(): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="maruderm-settings__card">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::CREATE_COUPON_ACTION) . '">';
        wp_nonce_field(self::CREATE_COUPON_ACTION);
        echo '<div class="maruderm-settings__card-heading"><span>WooCommerce Coupons</span><h2>Створити новий купон</h2><p>Купон буде створено як стандартний WooCommerce coupon і залишиться доступним у Marketing → Coupons.</p></div>';
        $this->renderCouponInput('coupon_code', 'Код купона', '', 'text', true);
        echo '<label class="maruderm-settings__field" for="maruderm-discount-type"><span>Тип знижки</span><select id="maruderm-discount-type" name="discount_type"><option value="percent">Відсоткова знижка</option><option value="fixed_cart">Фіксована знижка кошика</option><option value="fixed_product">Фіксована знижка товару</option></select></label>';
        $this->renderCouponInput('coupon_amount', 'Розмір знижки', '0', 'number', true, '0.01');
        $this->renderCouponInput('usage_limit', 'Ліміт використань (0 — без ліміту)', '0', 'number', false, '1');
        $this->renderCouponInput('date_expires', 'Дата завершення', '', 'date');
        echo '<label class="maruderm-settings__field" for="maruderm-coupon-description"><span>Опис</span><textarea id="maruderm-coupon-description" name="description" rows="3"></textarea></label>';
        echo '<div class="maruderm-bonuses-settings__checks"><label><input type="checkbox" name="individual_use" value="1"> Індивідуальне використання</label><label><input type="checkbox" name="free_shipping" value="1"> Безкоштовна доставка</label></div>';
        echo '<div class="maruderm-settings__actions"><p>Після створення можна додати обмеження товарів, категорій і користувачів у редакторі WooCommerce.</p>';
        submit_button('Create WooCommerce Coupon', 'primary', 'submit', false);
        echo '</div></form>';
    }

    private function renderCouponList(): void
    {
        $couponIds = get_posts([
            'post_type' => 'shop_coupon',
            'post_status' => ['publish', 'draft'],
            'fields' => 'ids',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        echo '<section class="maruderm-settings__card maruderm-bonuses-settings__coupons"><div class="maruderm-settings__card-heading"><span>Coupon library</span><h2>Останні купони WooCommerce</h2><p><a href="' . esc_url(admin_url('edit.php?post_type=shop_coupon')) . '">Керувати всіма купонами →</a></p></div>';

        if ($couponIds === []) {
            echo '<p>Купонів ще немає.</p></section>';
            return;
        }

        echo '<div class="maruderm-bonuses-settings__coupon-list"><div><strong>Код</strong><strong>Тип</strong><strong>Знижка</strong><strong>Використано</strong><strong></strong></div>';

        foreach ($couponIds as $couponId) {
            $coupon = new \WC_Coupon($couponId);
            $amount = $coupon->get_discount_type() === 'percent'
                ? wc_format_localized_decimal($coupon->get_amount()) . '%'
                : wp_strip_all_tags(wc_price((float) $coupon->get_amount()));
            echo '<div><code>' . esc_html($coupon->get_code()) . '</code><span>' . esc_html(wc_get_coupon_type($coupon->get_discount_type())) . '</span><span>' . esc_html($amount) . '</span><span>' . esc_html((string) $coupon->get_usage_count()) . '</span><a class="button" href="' . esc_url(admin_url('post.php?post=' . $couponId . '&action=edit')) . '">Редагувати</a></div>';
        }

        echo '</div></section>';
    }

    private function renderNotice(): void
    {
        $status = sanitize_key(wp_unslash((string) ($_GET['coupon_status'] ?? '')));

        if ($status === '') {
            return;
        }

        $messages = [
            'created' => ['success', 'Купон WooCommerce створено.'],
            'missing' => ['error', 'Вкажіть код купона.'],
            'duplicate' => ['error', 'Купон із таким кодом уже існує.'],
        ];

        if (!isset($messages[$status])) {
            return;
        }

        [$type, $message] = $messages[$status];
        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message);

        if ($status === 'created' && absint($_GET['coupon_id'] ?? 0) > 0) {
            echo ' <a href="' . esc_url(admin_url('post.php?post=' . absint($_GET['coupon_id']) . '&action=edit')) . '">Відкрити купон</a>';
        }

        echo '</p></div>';
    }

    private function renderNumberField(string $field, string $label, float $value, float $minimum, string $step): void
    {
        echo '<label class="maruderm-settings__field" for="maruderm-' . esc_attr($field) . '"><span>' . esc_html($label) . '</span><input id="maruderm-' . esc_attr($field) . '" type="number" min="' . esc_attr((string) $minimum) . '" step="' . esc_attr($step) . '" name="' . esc_attr(self::OPTION_NAME . '[' . $field . ']') . '" value="' . esc_attr((string) $value) . '"></label>';
    }

    private function renderTierField(string $tier, string $field, string $label, string $value, bool $readonly = false): void
    {
        $id = 'maruderm-' . $tier . '-' . $field;
        $type = $field === 'threshold' ? 'number' : 'text';
        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '"><span>' . esc_html($label) . '</span><input id="' . esc_attr($id) . '" type="' . esc_attr($type) . '" name="' . esc_attr(self::OPTION_NAME . '[tiers][' . $tier . '][' . $field . ']') . '" value="' . esc_attr($value) . '"' . ($readonly ? ' readonly' : '') . '></label>';
    }

    private function renderCouponInput(string $name, string $label, string $value, string $type, bool $required = false, string $step = ''): void
    {
        $id = 'maruderm-' . str_replace('_', '-', $name);
        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '"><span>' . esc_html($label) . '</span><input id="' . esc_attr($id) . '" type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . ($step !== '' ? ' step="' . esc_attr($step) . '" min="0"' : '') . ($required ? ' required' : '') . '></label>';
    }

    /** @return array{points_per_uah: float, tiers: array<string, array{name: string, threshold: int}>} */
    private function defaults(): array
    {
        return [
            'points_per_uah' => 10.0,
            'tiers' => [
                'member' => ['name' => 'Member', 'threshold' => 0],
                'bronze' => ['name' => 'Bronze', 'threshold' => 10000],
                'silver' => ['name' => 'Silver', 'threshold' => 50000],
                'gold' => ['name' => 'Gold', 'threshold' => 100000],
            ],
        ];
    }
}
