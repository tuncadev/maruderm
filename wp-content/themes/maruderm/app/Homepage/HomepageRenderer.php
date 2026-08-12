<?php

declare(strict_types=1);

namespace Maruderm\Homepage;

if (!defined('ABSPATH')) {
    exit();
}

final class HomepageRenderer
{
    private HomepageContent $content;

    public function __construct(?HomepageContent $content = null)
    {
        $this->content = $content ?? new HomepageContent();
    }

    public function render(): void
    {
        $this->prime_post_context();

        echo '<div class="maruderm-homepage elementor-page">';
        $this->render_slider();
        $this->render_benefits();
        $this->render_deals();
        $this->render_banners();
        $this->render_categories();
        $this->render_product_carousels();
        $this->render_product_list();
        echo '</div>';
    }

    private function render_slider(): void
    {
        echo '<section class="maruderm-home-slider mf-slides navigation-dots">';
        echo '<div class="maruderm-home-slider__track js-maruderm-home-slider">';

        foreach ($this->content->slides() as $slide) {
            $image_url = $this->attachment_url((int) $slide['image_id'], 'full');
            $styles = [
                'background-color:' . $slide['background_color'],
                'background-size:' . $slide['background_size'],
                'background-position:' . $slide['background_position'],
            ];

            if ($image_url !== '') {
                $styles[] = 'background-image:url(' . esc_url($image_url) . ')';
            }

            $classes = 'maruderm-home-slider__slide slick-slide-inner';
            if (!empty($slide['light'])) {
                $classes .= ' maruderm-home-slider__slide--light';
            }

            echo '<article class="' . esc_attr($classes) . '" style="' . esc_attr(implode(';', $styles)) . '">';
            echo '<div class="maruderm-home-slider__content">';
            echo '<span class="maruderm-home-slider__eyebrow">' . esc_html($slide['heading']) . '</span>';
            echo '<h2 class="maruderm-home-slider__title" style="--slide-accent:' . esc_attr($slide['accent_color']) . '">';
            echo wp_kses($slide['description'], ['br' => [], 'span' => []]);
            echo '</h2>';
            echo '<a class="maruderm-home-slider__button" href="' . esc_url($slide['url']) . '">' . esc_html($slide['button_text']) . '</a>';
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }

    private function render_benefits(): void
    {
        echo '<section class="maruderm-home-section maruderm-benefits mf-elementor-icons-list mf-elementor-icons-list__display-flex mf-elementor-icons-horizontal-yes">';
        echo '<div class="container"><div class="icons-list-wrapper">';

        foreach ($this->content->benefits() as $benefit) {
            echo '<div class="box-item">';
            echo '<div class="martfury-icon-box mf-icon-left">';
            echo '<div class="box-wrapper">';
            echo '<span class="box-icon"><i class="' . esc_attr($benefit['icon']) . '"></i></span>';
            echo '<span class="box-title">' . esc_html($benefit['title']) . '</span>';
            echo '<span class="desc">' . esc_html($benefit['description']) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<span class="separator"></span>';
            echo '</div>';
        }

        echo '</div></div>';
        echo '</section>';
    }

    private function render_deals(): void
    {
        if (!class_exists('\MartfuryAddons\Elementor')) {
            return;
        }

        $settings = $this->content->deals();
        $settings['columns'] = (int) $settings['slidesToShow'];
        $settings['pagination'] = 'no';
        $product_deals = \MartfuryAddons\Elementor::get_product_deals($settings);

        if ($product_deals === '') {
            return;
        }

        $now = strtotime(current_time('Y-m-d H:i:s'));
        $expire = strtotime('00:00 +1 day', $now) - $now;
        $carousel_settings = [
            'slidesToScroll' => $settings['slidesToScroll'],
            'slidesToScroll_tablet' => $settings['slidesToScroll_tablet'],
            'slidesToScroll_mobile' => $settings['slidesToScroll_mobile'],
            'slidesToShow' => $settings['slidesToShow'],
            'slidesToShow_tablet' => $settings['slidesToShow_tablet'],
            'slidesToShow_mobile' => $settings['slidesToShow_mobile'],
            'infinite' => $settings['infinite'],
            'autoplay' => $settings['autoplay'],
            'autoplay_speed' => $settings['autoplay_speed'],
        ];

        echo '<section class="deals-of-day">';
        echo '<div class="container">';
        echo '<div class="mf-product-deals-day mf-elementor-product-deals-carousel woocommerce mf-elementor-navigation navigation-arrows navigation-tablet-dots navigation-mobile-dots deals-sale" data-settings="' . esc_attr(wp_json_encode($carousel_settings)) . '">';
        echo '<div class="cat-header">';
        echo '<div class="header-content">';
        echo '<h3 class="cat-title">' . esc_html($settings['title']) . '</h3>';
        echo '<div class="header-countdown"><span class="ends-text">' . esc_html($settings['ends_in']) . '</span><div class="martfury-countdown" data-expire="' . esc_attr((string) $expire) . '"></div></div>';
        echo '</div>';
        echo '<div class="header-link"><a class="box-title" href="' . esc_url($settings['view_all_link']['url']) . '">' . esc_html($settings['view_all_text']) . '</a></div>';
        echo '</div>';
        echo '<div class="products-content">' . $product_deals . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }

    private function render_banners(): void
    {
        echo '<section class="two-banners"><div class="container"><div class="maruderm-banner-grid">';

        foreach ($this->content->banners() as $banner) {
            echo '<div class="mf-elementor-banner-medium maruderm-banner">';
            echo '<div class="banner-content">';
            echo '<h2 class="banner-title">' . wp_kses($banner['title'], ['br' => []]) . '</h2>';
            echo '<a class="btn-button" href="' . esc_url($banner['url']) . '">' . esc_html($banner['button_text']) . '</a>';
            echo '</div>';
            echo '<div class="banner-featured-image">' . $this->attachment_image((int) $banner['image_id'], 'large') . '</div>';
            echo '<a class="link" href="' . esc_url($banner['url']) . '" aria-label="' . esc_attr(strip_tags($banner['title'])) . '"></a>';
            echo '</div>';
        }

        echo '</div></div></section>';
    }

    private function render_categories(): void
    {
        echo '<section class="top-categories-month"><div class="container">';
        echo '<h2 class="maruderm-section-title">Найкращі категорії місяця</h2>';
        echo '<div class="elementor-container maruderm-category-grid">';

        foreach ($this->content->categories() as $category) {
            echo '<div class="elementor-column category-item">';
            echo '<div class="mf-elementor-image-box">';
            echo '<a class="thumbnail" href="' . esc_url($category['url']) . '">' . $this->attachment_image((int) $category['image_id'], 'medium') . '</a>';
            echo '<div class="image-content"><h2 class="box-title"><a href="' . esc_url($category['url']) . '">' . esc_html($category['title']) . '</a></h2></div>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div></div></section>';
    }

    private function render_product_carousels(): void
    {
        foreach ($this->content->productCarousels() as $carousel) {
            echo '<section class="maruderm-home-section maruderm-products-section"><div class="container">';
            echo $this->render_products_carousel($carousel);
            echo '</div></section>';
        }
    }

    private function render_products_carousel(array $settings): string
    {
        $atts = [
            'per_page' => 12,
            'products' => 'recent',
            'order' => '',
            'orderby' => '',
            'product_cats' => $settings['product_cats'],
            'product_tags' => [],
            'product_brands' => [],
            'columns' => 5,
        ];

        $products = $this->products_html($atts);
        $carousel_settings = [
            'autoplay' => 'no',
            'infinite' => 'yes',
            'autoplay_speed' => 3000,
            'speed' => 800,
            'slidesToShow' => 5,
            'slidesToScroll' => 5,
            'slidesToShow_tablet' => 3,
            'slidesToScroll_tablet' => 3,
            'slidesToShow_mobile' => 2,
            'slidesToScroll_mobile' => 2,
            'arrows_background' => '',
        ];

        $html = '<div class="mf-products-carousel woocommerce mf-elementor-navigation navigation-arrows navigation-tablet-dots navigation-mobile-dots no-infinite" data-settings="' . esc_attr(wp_json_encode($carousel_settings)) . '">';
        $html .= '<div class="cat-header">';
        $html .= '<h2 class="cat-title">' . esc_html($settings['title']) . '</h2>';
        $html .= $this->links_html($settings['links']);
        $html .= '</div>';
        $html .= '<div class="products-content">' . $products . '</div>';
        $html .= '</div>';

        return $html;
    }

    private function render_product_list(): void
    {
        $settings = $this->content->productList();
        $atts = [
            'per_page' => 6,
            'products' => 'recent',
            'order' => '',
            'orderby' => '',
            'product_cats' => $settings['product_cats'],
            'columns' => 3,
        ];

        echo '<section class="maruderm-home-section maruderm-products-section"><div class="container">';
        echo '<div class="mf-products-list mf-products woocommerce">';
        echo '<div class="cat-header">';
        echo '<h2 class="cat-title">' . esc_html($settings['title']) . '</h2>';
        echo $this->links_html($settings['links']);
        echo '</div>';
        echo '<div class="products-content">' . $this->products_html($atts) . '</div>';
        echo '</div>';
        echo '</div></section>';
    }

    private function products_html(array $atts): string
    {
        if (class_exists('\MartfuryAddons\Elementor')) {
            return \MartfuryAddons\Elementor::get_products($atts);
        }

        $category = '';
        if (!empty($atts['product_cats'])) {
            $category = ' category="' . esc_attr(implode(',', (array) $atts['product_cats'])) . '"';
        }

        return do_shortcode(sprintf(
            '[products columns="%d" limit="%d" order="%s" orderby="%s"%s]',
            (int) $atts['columns'],
            (int) $atts['per_page'],
            esc_attr((string) $atts['order']),
            esc_attr((string) $atts['orderby']),
            $category
        ));
    }

    private function links_html(array $links): string
    {
        if ($links === []) {
            return '';
        }

        $html = '<ul class="extra-links">';

        foreach ($links as $link) {
            $html .= '<li><a class="extra-link" href="#">' . esc_html($link) . '</a></li>';
        }

        $html .= '</ul>';

        return $html;
    }

    private function attachment_image(int $attachment_id, string $size): string
    {
        $image = wp_get_attachment_image($attachment_id, $size, false, ['loading' => 'lazy']);

        if ($image !== '') {
            return $image;
        }

        return '';
    }

    private function attachment_url(int $attachment_id, string $size): string
    {
        $url = wp_get_attachment_image_url($attachment_id, $size);

        return is_string($url) ? $url : '';
    }

    private function prime_post_context(): void
    {
        if (isset($GLOBALS['post']) && $GLOBALS['post'] instanceof \WP_Post) {
            return;
        }

        $queried_object = get_queried_object();

        if ($queried_object instanceof \WP_Post) {
            $GLOBALS['post'] = $queried_object;

            return;
        }

        $front_page_id = (int) get_option('page_on_front');

        if ($front_page_id <= 0) {
            return;
        }

        $front_page = get_post($front_page_id);

        if ($front_page instanceof \WP_Post) {
            $GLOBALS['post'] = $front_page;
        }
    }
}
