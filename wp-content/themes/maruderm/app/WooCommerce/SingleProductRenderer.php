<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the HTML-reference product page with live WooCommerce data. */
final class SingleProductRenderer
{
    private SingleProductContent $content;
    private ProductBadges $badges;
    private StockNotificationRenderer $stock_notifications;
    private ProductCardRenderer $product_cards;

    public function __construct(
        ?SingleProductContent $content = null,
        ?ProductBadges $badges = null,
        ?StockNotificationRenderer $stock_notifications = null,
        ?ProductCardRenderer $product_cards = null
    ) {
        $this->content = $content ?? new SingleProductContent();
        $this->badges = $badges ?? new ProductBadges();
        $this->stock_notifications = $stock_notifications ?? new StockNotificationRenderer();
        $this->product_cards = $product_cards ?? new ProductCardRenderer();
    }

    public function render(\WC_Product $product): void
    {
        $category = $this->content->category($product);
        $category_name = $category?->name ?? 'Maruderm';
        $category_url = $category instanceof \WP_Term ? get_term_link($category) : wc_get_page_permalink('shop');
        $category_url = is_wp_error($category_url) ? wc_get_page_permalink('shop') : $category_url;
        $images = $this->content->imageIds($product);
        $main_image = $images[0] ?? 0;
        $badge = $this->badges->resolve($product);
        $rating = (float) $product->get_average_rating();
        $review_count = $product->get_review_count();

        echo '<main class="maruderm-product-page maruderm-catalog" data-product-page data-product-id="' . esc_attr((string) $product->get_id()) . '">';
        woocommerce_output_all_notices();
        ?>
        <section class="product-detail">
            <div class="shell">
                <nav class="breadcrumbs product-breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="<?= esc_url(home_url('/')); ?>">Головна</a><span>/</span><a href="<?= esc_url(wc_get_page_permalink('shop')); ?>">Каталог</a><span>/</span><span><?= esc_html($product->get_name()); ?></span></nav>
                <div class="product-detail__grid">
                    <div class="product-gallery" data-product-gallery>
                        <div class="product-gallery__thumbs" aria-label="Зображення товару">
                            <?php foreach ($this->gallerySlots($images) as $index => $image_id) : ?>
                                <button class="product-gallery__thumb<?= $index === 0 ? ' is-active' : ''; ?><?= $index === 1 ? ' product-gallery__thumb--lilac' : ''; ?><?= $index === 2 ? ' product-gallery__thumb--detail' : ''; ?>" type="button" data-gallery-image="<?= esc_attr((string) $image_id); ?>" data-gallery-src="<?= esc_url((string) wp_get_attachment_image_url($image_id, 'woocommerce_single')); ?>" data-gallery-srcset="<?= esc_attr((string) wp_get_attachment_image_srcset($image_id, 'woocommerce_single')); ?>" data-gallery-view="<?= esc_attr(['clean', 'lilac', 'detail'][$index]); ?>" aria-label="Зображення товару <?= esc_attr((string) ($index + 1)); ?>"><?= wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, ['alt' => $product->get_name(), 'loading' => 'lazy']); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="product-gallery__stage" data-gallery-stage data-view="clean">
                            <?php if ($badge !== null) : ?><span class="product-gallery__badge product-gallery__badge--<?= esc_attr($badge['tone']); ?> maruderm-product-badge maruderm-product-badge--<?= esc_attr($badge['tone']); ?>"><?= esc_html($badge['label']); ?></span><?php endif; ?>
                            <button class="product-gallery__wishlist" type="button" aria-label="Додати товар в обране" data-product-wishlist><?= $this->heartIcon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
                            <span class="product-gallery__orbit product-gallery__orbit--one"></span><span class="product-gallery__orbit product-gallery__orbit--two"></span>
                            <span class="product-gallery__main-image" role="img" aria-label="<?= esc_attr($product->get_name()); ?>" data-gallery-main style="background-image:url('<?= esc_url((string) wp_get_attachment_image_url($main_image, 'woocommerce_single')); ?>')"></span>
                            <span class="product-gallery__note">skin ritual</span>
                        </div>
                    </div>
                    <div class="product-summary">
                        <a class="product-summary__category" href="<?= esc_url($category_url); ?>"><?= esc_html($category_name); ?></a>
                        <h1><?= esc_html($product->get_name()); ?></h1>
                        <div class="product-summary__rating"><span class="product-summary__stars" aria-label="<?= esc_attr(number_format($rating, 1) . ' з 5 зірок'); ?>"><?= esc_html($this->stars($rating)); ?></span><a href="#product-reviews"><?= esc_html($review_count > 0 ? number_format($rating, 1) . ' · ' . $review_count . ' відгуків' : 'Ще немає відгуків'); ?></a></div>
                        <p class="product-summary__lead"><?= esc_html($this->content->lead($product)); ?></p>
                        <div class="product-summary__price"><?= wp_kses_post($product->get_price_html()); ?></div>
                        <div class="product-summary__highlights"><?php foreach ($this->content->highlights($product) as $highlight) : ?><span><?= esc_html($highlight); ?></span><?php endforeach; ?></div>
                        <?php $this->renderPurchase($product); ?>
                        <p class="product-summary__availability" data-stock-status="<?= $product->is_in_stock() ? 'in-stock' : 'out-of-stock'; ?>"><span></span><span><?= esc_html($product->is_in_stock() ? 'В наявності · відправимо протягом 1–2 днів' : 'Наразі немає в наявності'); ?></span></p>
                        <div class="product-perks"><article><span class="product-perks__icon">01</span><div><strong>Оригінальна продукція</strong><small>Пряме постачання Maruderm</small></div></article><article><span class="product-perks__icon">02</span><div><strong>Безкоштовна доставка</strong><small>Для замовлень від 1500 ₴</small></div></article></div>
                        <?php $this->renderAccordions($product); ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        $this->renderBenefits($product);
        $this->renderIngredients($product);
        $this->renderRoutine($product);
        $this->renderReviews($product);
        $this->renderRelated($product, $category_url);
        echo '</main>';
    }

    /** @param int[] $images @return int[] */
    private function gallerySlots(array $images): array
    {
        if ($images === []) {
            return [];
        }

        while (count($images) < 3) {
            $images[] = $images[0];
        }

        return array_slice($images, 0, 3);
    }

    private function renderPurchase(\WC_Product $product): void
    {
        echo '<div class="product-buy">';
        if ($product->is_in_stock() && $product->is_purchasable()) {
            $minimum = max(1, (int) apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product));
            $maximum = (int) apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product);
            $step = (float) apply_filters('woocommerce_quantity_input_step', 1, $product);

            echo '<form class="cart product-buy__form" action="' . esc_url($product->get_permalink()) . '" method="post" enctype="multipart/form-data"><div class="quantity-control" aria-label="Кількість товару"><button type="button" data-quantity-minus aria-label="Зменшити кількість">−</button>';
            echo '<input class="qty" type="number" name="quantity" value="' . esc_attr((string) $minimum) . '" min="' . esc_attr((string) $minimum) . '"' . ($maximum > 0 ? ' max="' . esc_attr((string) $maximum) . '"' : '') . ' step="' . esc_attr((string) $step) . '" inputmode="numeric" autocomplete="off" aria-label="Кількість товару" data-quantity>';
            echo '<button type="button" data-quantity-plus aria-label="Збільшити кількість">+</button></div><button type="submit" name="add-to-cart" value="' . esc_attr((string) $product->get_id()) . '" class="single_add_to_cart_button product-buy__button"><span>Додати до кошика</span>' . $this->bagIcon() . '</button></form>';
        } else {
            echo $this->stock_notifications->singleButton($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
    }

    private function renderAccordions(\WC_Product $product): void
    {
        $description = $product->get_description() !== '' ? $product->get_description() : $this->content->lead($product);
        $ingredients = implode(', ', array_column($this->content->ingredients($product), 'title'));
        $items = [
            ['Про продукт', wp_kses_post(wpautop($description))],
            ['Ключові компоненти', '<p>' . esc_html($ingredients) . '.</p>'],
            ['Доставка й повернення', '<p>Відправлення Новою поштою протягом 1–2 робочих днів. Повернення відповідно до чинних правил для косметичної продукції.</p>'],
        ];
        echo '<div class="product-accordions">';
        foreach ($items as $index => [$title, $copy]) {
            echo '<article class="product-accordion' . ($index === 0 ? ' is-open' : '') . '"><button type="button" aria-expanded="' . ($index === 0 ? 'true' : 'false') . '"><span>' . esc_html($title) . '</span><i></i></button><div>' . $copy . '</div></article>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
    }

    private function renderBenefits(\WC_Product $product): void
    {
        echo '<section class="section product-benefits"><div class="shell"><header class="product-section-heading"><span class="kicker">Коли формула має сенс</span><h2>Результат, який відчувається.</h2><p>Кожен компонент має чітке завдання — підтримувати ефективний і комфортний догляд.</p></header><div class="product-benefits__grid">';
        foreach ($this->content->benefits($product) as $index => $benefit) {
            $tones = ['mint', 'lilac', 'peach'];
            echo '<article class="product-benefit product-benefit--' . esc_attr($tones[$index]) . '"><span>0' . esc_html((string) ($index + 1)) . '</span><h3>' . esc_html($benefit['title']) . '</h3><p>' . esc_html($benefit['text']) . '</p></article>';
        }
        echo '</div></div></section>';
    }

    private function renderIngredients(\WC_Product $product): void
    {
        echo '<section class="section product-ingredients"><div class="shell product-ingredients__grid"><div class="ingredient-visual" aria-hidden="true"><span class="ingredient-visual__circle ingredient-visual__circle--large"></span><span class="ingredient-visual__circle ingredient-visual__circle--small"></span><div class="ingredient-visual__formula"><small>active blend</small><strong>' . wp_kses($this->content->formula($product), ['br' => []]) . '</strong><span>focused care</span></div><span class="ingredient-visual__label ingredient-visual__label--one">active care</span><span class="ingredient-visual__label ingredient-visual__label--two">daily ritual</span></div><div class="product-ingredients__copy"><span class="kicker">Всередині формули</span><h2>Активи працюють.<br>Ритуал залишається легким.</h2><p>Ключові компоненти поєднані так, щоб доповнювати регулярний домашній догляд.</p><dl>';
        foreach ($this->content->ingredients($product) as $ingredient) {
            echo '<div><dt>' . esc_html($ingredient['title']) . '</dt><dd>' . esc_html($ingredient['text']) . '</dd></div>';
        }
        echo '</dl></div></div></section>';
    }

    private function renderRoutine(\WC_Product $product): void
    {
        echo '<section class="section product-use"><div class="shell product-use__grid"><div class="product-use__intro"><span class="kicker">Як використовувати</span><h2>Кілька простих кроків до регулярного догляду.</h2><p>Дотримуйся рекомендацій на пакованні та використовуй засіб послідовно.</p></div><ol class="product-use__steps">';
        foreach ($this->content->routine($product) as $index => $step) {
            echo '<li><span>0' . esc_html((string) ($index + 1)) . '</span><div><h3>' . esc_html($step['title']) . '</h3><p>' . esc_html($step['text']) . '</p></div></li>';
        }
        echo '</ol></div></section>';
    }

    private function renderReviews(\WC_Product $product): void
    {
        $comments = get_comments(['post_id' => $product->get_id(), 'status' => 'approve', 'number' => 1, 'type' => 'review']);
        $review = $comments[0] ?? null;
        $count = $product->get_review_count();
        $rating = (float) $product->get_average_rating();
        echo '<section class="section product-reviews" id="product-reviews"><div class="shell product-reviews__grid"><div class="product-review-score"><span class="kicker">Відгуки покупців</span><strong>' . esc_html($count > 0 ? number_format($rating, 1) : '—') . '</strong><div aria-label="' . esc_attr(number_format($rating, 1) . ' з 5 зірок') . '">' . esc_html($this->stars($rating)) . '</div><p>' . esc_html($count > 0 ? 'На основі ' . $count . ' відгуків' : 'Відгуків поки немає') . '</p></div><blockquote><p>';
        if ($review instanceof \WP_Comment) {
            echo '“' . esc_html(wp_strip_all_tags($review->comment_content)) . '”</p><footer><span>' . esc_html($review->comment_author) . '</span><small>Підтверджений відгук</small></footer>';
        } else {
            echo 'Будь першим, хто поділиться враженнями про цей продукт.</p><footer><span>Твій досвід важливий</span><small>Допоможи іншим обрати догляд</small></footer>';
        }
        echo '</blockquote></div></section>';
    }

    private function renderRelated(\WC_Product $product, string $category_url): void
    {
        $related = $this->content->related($product);
        if ($related === []) {
            return;
        }
        echo '<section class="section product-related"><div class="shell"><header class="section-heading"><div><span class="kicker">Продовжуй ритуал</span><h2>Добре працюють разом</h2></div><a class="text-link" href="' . esc_url($category_url) . '">Дивитися догляд ' . $this->arrowIcon() . '</a></header><div class="product-grid" data-related-products>';
        foreach ($related as $related_product) {
            echo $this->product_cards->render($related_product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div></div></section>';
    }

    private function stars(float $rating): string
    {
        return str_repeat('★', (int) round($rating)) . str_repeat('☆', 5 - (int) round($rating));
    }

    private function heartIcon(): string { return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"></path></svg>'; }
    private function bagIcon(): string { return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path></svg>'; }
    private function arrowIcon(): string { return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"></path></svg>'; }
}
