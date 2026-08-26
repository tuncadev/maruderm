<?php

declare(strict_types=1);

namespace Maruderm\Settings;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class HomepageSettings implements Registrable
{
    use Loadable;

    public const OPTION_NAME = 'maruderm_homepage_settings';
    public const PAGE_SLUG = 'maruderm-homepage-settings';

    private const SETTINGS_GROUP = 'maruderm_homepage_settings_group';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            ThemeSettings::PAGE_SLUG,
            'Homepage Settings',
            'Homepage Settings',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
                'default' => [],
            ]
        );
    }

    public function enqueueAssets(string $hook_suffix): void
    {
        if ($hook_suffix !== ThemeSettings::PAGE_SLUG . '_page_' . self::PAGE_SLUG) {
            return;
        }

        $asset_path = get_theme_file_path('assets/admin/homepage-settings.css');

        wp_enqueue_style(
            'maruderm-homepage-settings',
            get_theme_file_uri('assets/admin/homepage-settings.css'),
            [],
            file_exists($asset_path) ? (string) filemtime($asset_path) : null
        );

        wp_enqueue_media();

        $script_path = get_theme_file_path('assets/admin/homepage-settings.js');

        wp_enqueue_script(
            'maruderm-homepage-settings',
            get_theme_file_uri('assets/admin/homepage-settings.js'),
            [],
            file_exists($script_path) ? (string) filemtime($script_path) : null,
            true
        );
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        $saved = is_array($saved) ? $saved : [];
        $sections = [];

        foreach ($this->definitions() as $key => $definition) {
            $value = isset($saved[$key]) && is_array($saved[$key]) ? $saved[$key] : [];
            $sections[$key] = array_merge($definition['defaults'], $value);
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    public function section(string $key): array
    {
        $sections = $this->all();

        return $sections[$key] ?? ['eyebrow' => '', 'heading' => '', 'description' => ''];
    }

    public function sanitize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $sanitized = [];

        foreach ($this->definitions() as $key => $definition) {
            $section = isset($input[$key]) && is_array($input[$key]) ? $input[$key] : [];
            $sanitized[$key] = [
                'eyebrow' => sanitize_text_field((string) ($section['eyebrow'] ?? '')),
                'heading' => wp_kses((string) ($section['heading'] ?? ''), ['em' => [], 'br' => []]),
                'description' => sanitize_textarea_field((string) ($section['description'] ?? '')),
            ];

            if ($key === 'hero') {
                $sanitized[$key]['product_id'] = $this->sanitizeProductId($section['product_id'] ?? 0);
                $sanitized[$key]['primary_category_id'] = $this->sanitizeCategoryId($section['primary_category_id'] ?? 0);
            }

            if ($key === 'categories') {
                $category_ids = $this->sanitizeCategoryIds($section['category_ids'] ?? []);
                $images = isset($section['category_images']) && is_array($section['category_images'])
                    ? $section['category_images']
                    : [];

                $sanitized[$key]['category_ids'] = $category_ids;
                $sanitized[$key]['category_images'] = [];

                foreach ($images as $category_id => $image_value) {
                    $category_id = absint($category_id);
                    $image_id = $this->sanitizeImageId($image_value);

                    if ($this->isProductCategory($category_id) && $image_id > 0) {
                        $sanitized[$key]['category_images'][$category_id] = $image_id;
                    }
                }
            }

            if ($key === 'new_products') {
                $sanitized[$key]['category_ids'] = $this->sanitizeCategoryIds($section['category_ids'] ?? []);
                $sanitized[$key]['product_limit'] = max(1, min(12, absint($section['product_limit'] ?? 8)));
            }

            if ($key === 'editorial') {
                $sanitized[$key]['primary_category_id'] = $this->sanitizeCategoryId($section['primary_category_id'] ?? 0);
                $sanitized[$key]['primary_image_id'] = $this->sanitizeImageId($section['primary_image_id'] ?? 0);
                $sanitized[$key]['secondary_category_id'] = $this->sanitizeCategoryId($section['secondary_category_id'] ?? 0);
                $sanitized[$key]['secondary_image_id'] = $this->sanitizeImageId($section['secondary_image_id'] ?? 0);
            }
        }

        return $sanitized;
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $values = $this->all();

        echo '<div class="wrap maruderm-settings">';
        echo '<header class="maruderm-settings__header"><div><span>Maruderm Settings</span><h1>Homepage Settings</h1><p>Керуйте текстом, категоріями, товарами та зображеннями блоків головної сторінки.</p></div><span class="maruderm-settings__status">Live storefront controls</span></header>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::SETTINGS_GROUP);
        echo '<div class="maruderm-settings__sections">';

        foreach ($this->definitions() as $key => $definition) {
            $section = $values[$key];
            echo '<section class="maruderm-settings__card">';
            echo '<div class="maruderm-settings__card-heading"><span>' . esc_html($definition['label']) . '</span><h2>' . wp_kses($section['heading'], ['em' => [], 'br' => []]) . '</h2><p>Налаштування вмісту цього блоку.</p></div>';
            $this->renderInput($key, 'eyebrow', 'Eyebrow', $section['eyebrow']);
            $this->renderInput($key, 'heading', 'Heading', $section['heading']);
            $this->renderTextarea($key, 'description', 'Description', $section['description']);
            $this->renderMerchandisingFields($key, $section);
            echo '</section>';
        }

        echo '</div><div class="maruderm-settings__actions"><p>Зміни з’являться на головній сторінці після збереження.</p>';
        submit_button('Save Homepage Settings', 'primary', 'submit', false);
        echo '</div>';
        echo '</form></div>';
    }

    /** @return array<string, array{label: string, defaults: array<string, mixed>}> */
    private function definitions(): array
    {
        return [
            'hero' => [
                'label' => 'Hero',
                'defaults' => [
                    'eyebrow' => 'Косметика нового покоління',
                    'heading' => 'Догляд, що працює <em>у твоєму ритмі.</em>',
                    'description' => 'Активні формули для щоденних ритуалів — зрозуміло, красиво й без зайвого.',
                    'product_id' => 0,
                    'primary_category_id' => 0,
                ],
            ],
            'categories' => [
                'label' => 'Categories',
                'defaults' => [
                    'eyebrow' => 'Обирай за категорією',
                    'heading' => 'З чого почнемо?',
                    'description' => 'Обирай категорію, щоб швидко перейти до потрібного догляду.',
                    'category_ids' => [],
                    'category_images' => [],
                ],
            ],
            'new_products' => [
                'label' => 'New products',
                'defaults' => [
                    'eyebrow' => 'Щойно у Maruderm',
                    'heading' => 'Новинки для твоєї полиці',
                    'description' => 'Найсвіжіші формули та засоби, які щойно з’явилися в каталозі.',
                    'category_ids' => [],
                    'product_limit' => 8,
                ],
            ],
            'editorial' => [
                'label' => 'Editorial',
                'defaults' => [
                    'eyebrow' => 'Тематичні добірки',
                    'heading' => 'Догляд як час для себе',
                    'description' => 'Добірки, що допомагають перетворити щоденний догляд на зрозумілий ритуал.',
                    'primary_category_id' => 0,
                    'primary_image_id' => 0,
                    'secondary_category_id' => 0,
                    'secondary_image_id' => 0,
                ],
            ],
            'routine' => [
                'label' => 'Routine',
                'defaults' => [
                    'eyebrow' => 'Три прості кроки',
                    'heading' => 'Рутина без перевантаження',
                    'description' => 'Послідовність, яку легко підтримувати щодня.',
                ],
            ],
            'closing' => [
                'label' => 'Closing callout',
                'defaults' => [
                    'eyebrow' => 'Твій догляд, твої правила',
                    'heading' => 'Знайди формули, які хочеться використовувати щодня.',
                    'description' => 'Побудуй власну рутину з формул, які відповідають потребам твоєї шкіри.',
                ],
            ],
        ];
    }

    private function renderInput(string $section, string $field, string $label, string $value): void
    {
        $id = 'maruderm-' . $section . '-' . $field;
        $name = self::OPTION_NAME . '[' . $section . '][' . $field . ']';

        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '">';
        echo '<span>' . esc_html($label) . '</span>';
        echo '<input class="regular-text" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        echo '</label>';
    }

    private function renderTextarea(string $section, string $field, string $label, string $value): void
    {
        $id = 'maruderm-' . $section . '-' . $field;
        $name = self::OPTION_NAME . '[' . $section . '][' . $field . ']';

        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '">';
        echo '<span>' . esc_html($label) . '</span>';
        echo '<textarea class="large-text" rows="3" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        echo '</label>';
    }

    /** @param array<string, mixed> $section */
    private function renderMerchandisingFields(string $key, array $section): void
    {
        if (!in_array($key, ['hero', 'categories', 'new_products', 'editorial'], true)) {
            return;
        }

        echo '<div class="maruderm-settings__merchandising"><h3>Content selection</h3>';

        if ($key === 'hero') {
            $this->renderProductSelect('hero', 'product_id', 'Featured product', (int) $section['product_id']);
            $this->renderCategorySelect('hero', 'primary_category_id', 'Primary button category', (int) $section['primary_category_id']);
        }

        if ($key === 'categories') {
            $this->renderCategoryImageList($section);
        }

        if ($key === 'new_products') {
            $this->renderCategoryCheckboxes('new_products', $section['category_ids']);
            $this->renderNumberInput('new_products', 'product_limit', 'Number of products', (int) $section['product_limit'], 1, 12);
        }

        if ($key === 'editorial') {
            $this->renderCategorySelect('editorial', 'primary_category_id', 'Primary category', (int) $section['primary_category_id']);
            $this->renderMediaField('editorial', 'primary_image_id', 'Primary custom image', (int) $section['primary_image_id']);
            $this->renderCategorySelect('editorial', 'secondary_category_id', 'Secondary category', (int) $section['secondary_category_id']);
            $this->renderMediaField('editorial', 'secondary_image_id', 'Secondary custom image', (int) $section['secondary_image_id']);
        }

        echo '</div>';
    }

    /** @return \WP_Term[] */
    private function productCategories(): array
    {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        return is_wp_error($categories) ? [] : $categories;
    }

    private function renderCategorySelect(string $section, string $field, string $label, int $selected): void
    {
        $id = 'maruderm-' . $section . '-' . $field;
        $name = self::OPTION_NAME . '[' . $section . '][' . $field . ']';

        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '"><span>' . esc_html($label) . '</span>';
        echo '<select class="widefat" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
        echo '<option value="0">Automatic</option>';

        foreach ($this->productCategories() as $category) {
            echo '<option value="' . esc_attr((string) $category->term_id) . '"' . selected($selected, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
        }

        echo '</select></label>';
    }

    private function renderProductSelect(string $section, string $field, string $label, int $selected): void
    {
        $id = 'maruderm-' . $section . '-' . $field;
        $name = self::OPTION_NAME . '[' . $section . '][' . $field . ']';
        $products = function_exists('wc_get_products')
            ? wc_get_products(['status' => 'publish', 'stock_status' => 'instock', 'limit' => -1, 'orderby' => 'name', 'order' => 'ASC', 'return' => 'objects'])
            : [];

        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '"><span>' . esc_html($label) . '</span>';
        echo '<select class="widefat" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
        echo '<option value="0">Latest in-stock product</option>';

        foreach ($products as $product) {
            if ($product instanceof \WC_Product) {
                echo '<option value="' . esc_attr((string) $product->get_id()) . '"' . selected($selected, $product->get_id(), false) . '>' . esc_html($product->get_name()) . '</option>';
            }
        }

        echo '</select></label>';
    }

    /** @param mixed $selected */
    private function renderCategoryCheckboxes(string $section, mixed $selected): void
    {
        $selected = is_array($selected) ? array_map('absint', $selected) : [];
        echo '<fieldset class="maruderm-settings__field"><span>Product categories</span><p class="description">Leave all unchecked to show products from every category.</p><div class="maruderm-settings__checks">';

        foreach ($this->productCategories() as $category) {
            $name = self::OPTION_NAME . '[' . $section . '][category_ids][]';
            echo '<label class="maruderm-settings__check"><input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr((string) $category->term_id) . '"' . checked(in_array($category->term_id, $selected, true), true, false) . '><span>' . esc_html($category->name) . '</span></label>';
        }

        echo '</div></fieldset>';
    }

    /** @param array<string, mixed> $section */
    private function renderCategoryImageList(array $section): void
    {
        $selected = is_array($section['category_ids']) ? array_map('absint', $section['category_ids']) : [];
        $images = is_array($section['category_images']) ? $section['category_images'] : [];

        echo '<fieldset class="maruderm-settings__field"><span>Displayed categories and images</span><p class="description">Leave all unchecked to display the automatic category selection. A custom image replaces the WooCommerce category image.</p><div class="maruderm-settings__category-list">';

        foreach ($this->productCategories() as $category) {
            $checkbox_name = self::OPTION_NAME . '[categories][category_ids][]';
            $image_id = (int) ($images[$category->term_id] ?? 0);
            echo '<div class="maruderm-settings__category-row">';
            echo '<label class="maruderm-settings__category-check maruderm-settings__check"><input type="checkbox" name="' . esc_attr($checkbox_name) . '" value="' . esc_attr((string) $category->term_id) . '"' . checked(in_array($category->term_id, $selected, true), true, false) . '><span><strong>' . esc_html($category->name) . '</strong></span></label>';
            $this->renderMediaField('categories', 'category_images][' . $category->term_id, 'Custom image', $image_id, true);
            echo '</div>';
        }

        echo '</div></fieldset>';
    }

    private function renderMediaField(string $section, string $field, string $label, int $value, bool $compact = false): void
    {
        $id = 'maruderm-' . $section . '-' . str_replace(['][', ']'], '-', $field);
        $name = self::OPTION_NAME . '[' . $section . '][' . $field . ']';
        $url = $value > 0 ? wp_get_attachment_image_url($value, 'thumbnail') : false;
        $classes = 'maruderm-settings__media' . ($compact ? ' is-compact' : '');

        echo '<div class="' . esc_attr($classes) . '" data-maruderm-media>';
        echo '<span class="maruderm-settings__media-label">' . esc_html($label) . '</span>';
        echo '<input type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" data-media-input>';
        echo '<img src="' . esc_url(is_string($url) ? $url : '') . '" alt="" data-media-preview' . ($url ? '' : ' hidden') . '>';
        echo '<span class="maruderm-settings__media-actions"><button type="button" class="button" data-media-choose>Choose image</button><button type="button" class="button-link-delete" data-media-clear' . ($value > 0 ? '' : ' hidden') . '>Remove</button></span>';
        echo '</div>';
    }

    private function renderNumberInput(string $section, string $field, string $label, int $value, int $min, int $max): void
    {
        $id = 'maruderm-' . $section . '-' . $field;
        $name = self::OPTION_NAME . '[' . $section . '][' . $field . ']';

        echo '<label class="maruderm-settings__field" for="' . esc_attr($id) . '"><span>' . esc_html($label) . '</span>';
        echo '<input class="small-text" type="number" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max) . '"></label>';
    }

    /** @return int[] */
    private function sanitizeCategoryIds(mixed $value): array
    {
        $ids = is_array($value) ? array_values(array_unique(array_map('absint', $value))) : [];

        return array_values(array_filter($ids, fn (int $id): bool => $this->isProductCategory($id)));
    }

    private function sanitizeCategoryId(mixed $value): int
    {
        $id = absint($value);

        return $this->isProductCategory($id) ? $id : 0;
    }

    private function isProductCategory(int $id): bool
    {
        return $id > 0 && term_exists($id, 'product_cat') !== null;
    }

    private function sanitizeProductId(mixed $value): int
    {
        $id = absint($value);

        if ($id < 1 || !function_exists('wc_get_product')) {
            return 0;
        }

        $product = wc_get_product($id);

        return $product instanceof \WC_Product
            && $product->get_status() === 'publish'
            && $product->is_in_stock()
                ? $id
                : 0;
    }

    private function sanitizeImageId(mixed $value): int
    {
        $id = absint($value);

        return $id > 0 && wp_attachment_is_image($id) ? $id : 0;
    }
}
