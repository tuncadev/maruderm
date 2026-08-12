<?php
/**
 * WooCommerce category menu.
 *
 * @package Maruderm
 */

if (!defined('ABSPATH')) {
    exit();
}

$categoryTerms = get_terms([
    'taxonomy' => 'product_cat',
    'parent' => 0,
    'hide_empty' => true,
    'orderby' => 'menu_order',
    'order' => 'ASC',
]);

if (is_wp_error($categoryTerms) || $categoryTerms === []) {
    return;
}

$categoryTerms = array_values($categoryTerms);
$shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$menuTones = ['coral', 'yellow', 'mint', 'lilac', 'blue'];
?>
<div class="maruderm-menu" data-maruderm-menu>
    <button class="maruderm-menu__toggle" type="button" aria-label="Відкрити меню" aria-expanded="false" data-maruderm-menu-toggle>
        <span></span><span></span><span></span>
    </button>

    <nav class="maruderm-menu__nav" aria-label="Каталог товарів" data-maruderm-menu-nav>
        <div class="maruderm-menu__mobile-head">
            <span>Каталог</span>
            <button type="button" aria-label="Закрити меню" data-maruderm-menu-close>&times;</button>
        </div>

        <div class="maruderm-menu__inner">
            <?php foreach ($categoryTerms as $index => $categoryTerm) :
                $categoryUrl = get_term_link($categoryTerm);
                $children = get_terms([
                    'taxonomy' => 'product_cat',
                    'parent' => $categoryTerm->term_id,
                    'hide_empty' => true,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                ]);
                $children = is_wp_error($children) ? [] : $children;
                $groups = $children === [] ? [] : array_chunk($children, (int) ceil(count($children) / 3));
                $groupLabels = ['Категорії', 'Обирають часто', 'Ще більше'];
                $customImageId = function_exists('get_field')
                    ? (int) get_field('submenu_panel_image', $categoryTerm)
                    : 0;
                $customEyebrow = function_exists('get_field')
                    ? trim((string) get_field('submenu_panel_eyebrow', $categoryTerm))
                    : '';
                $customText = function_exists('get_field')
                    ? trim((string) get_field('submenu_panel_text', $categoryTerm))
                    : '';
                $categoryDescription = trim(wp_strip_all_tags((string) $categoryTerm->description));
                $categoryTitle = $customText !== ''
                    ? $customText
                    : ($categoryDescription !== '' ? wp_trim_words($categoryDescription, 9, '…') : $categoryTerm->name);
                $categoryEyebrow = $customEyebrow !== ''
                    ? $customEyebrow
                    : number_format_i18n($categoryTerm->count) . ' товарів';
                $menuTone = $menuTones[$index % count($menuTones)];
                $imageId = $customImageId > 0
                    ? $customImageId
                    : (int) get_term_meta($categoryTerm->term_id, 'thumbnail_id', true);

                if ($imageId === 0 && class_exists('WC_Product_Query')) {
                    $productQuery = new WC_Product_Query([
                        'status' => 'publish',
                        'limit' => 1,
                        'category' => [$categoryTerm->slug],
                        'orderby' => 'popularity',
                        'return' => 'objects',
                    ]);
                    $featuredProducts = $productQuery->get_products();
                    if ($featuredProducts !== []) {
                        $imageId = (int) $featuredProducts[0]->get_image_id();
                    }
                }

                $imageUrl = $imageId > 0 ? wp_get_attachment_image_url($imageId, 'medium') : '';
                $dropdownId = 'maruderm-menu-dropdown-' . (int) $categoryTerm->term_id;
                ?>
                <div class="maruderm-menu__item" data-maruderm-menu-item>
                    <div class="maruderm-menu__trigger">
                        <a href="<?php echo esc_url(is_wp_error($categoryUrl) ? $shopUrl : $categoryUrl); ?>">
                            <?php echo esc_html($categoryTerm->name); ?>
                        </a>
                        <?php if ($children !== []) : ?>
                            <button type="button" aria-label="Відкрити підкатегорії" aria-expanded="false" aria-controls="<?php echo esc_attr($dropdownId); ?>" data-maruderm-dropdown-toggle>
                                <i aria-hidden="true"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($children !== []) : ?>
                        <div class="maruderm-menu__dropdown" id="<?php echo esc_attr($dropdownId); ?>">
                            <div class="maruderm-menu__dropdown-inner container">
                                <div class="maruderm-menu__intro maruderm-menu__intro--<?php echo esc_attr($menuTone); ?>"<?php echo $imageUrl ? ' style="--maruderm-menu-art: url(\'' . esc_url($imageUrl) . '\')"' : ''; ?>>
                                    <span><?php echo esc_html($categoryEyebrow); ?></span>
                                    <strong><?php echo esc_html($categoryTitle); ?></strong>
                                    <a href="<?php echo esc_url(is_wp_error($categoryUrl) ? $shopUrl : $categoryUrl); ?>">Дивитися все →</a>
                                </div>

                                <?php foreach ($groups as $groupIndex => $group) : ?>
                                    <div class="maruderm-menu__group">
                                        <span><?php echo esc_html($groupLabels[$groupIndex] ?? 'Категорії'); ?></span>
                                        <?php foreach ($group as $childTerm) :
                                            $childUrl = get_term_link($childTerm);
                                            ?>
                                            <a href="<?php echo esc_url(is_wp_error($childUrl) ? $shopUrl : $childUrl); ?>">
                                                <?php echo esc_html($childTerm->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <a class="maruderm-menu__accent" href="<?php echo esc_url($shopUrl); ?>">Усі товари</a>
        </div>
    </nav>

    <button class="maruderm-menu__overlay" type="button" aria-label="Закрити меню" data-maruderm-menu-overlay></button>
</div>
