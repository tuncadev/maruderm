<?php

declare(strict_types=1);

namespace Maruderm\Layout;

use Maruderm\LandingPage\LandingPageCatalog;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the canonical storefront footer with live WordPress links. */
final class FooterRenderer
{
    private LandingPageCatalog $catalog;

    public function __construct(?LandingPageCatalog $catalog = null)
    {
        $this->catalog = $catalog ?? new LandingPageCatalog();
    }

    public function render(): void
    {
        echo '<footer id="colophon" class="site-footer"><div class="shell footer-grid">';
        echo '<div class="footer-brand">' . $this->logo();
        echo '<p>Дієві формули для щоденного догляду. Зрозуміло, красиво й без зайвого.</p></div>';
        echo '<div><h3>Каталог</h3>';

        foreach ($this->catalog->categories(4) as $category) {
            echo '<a href="' . esc_url($this->catalog->categoryUrl($category)) . '">' . esc_html($category->name) . '</a>';
        }

        echo '</div><div><h3>Допомога</h3>';

        foreach ($this->helpLinks() as $label => $url) {
            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }

        echo '</div><div class="footer-subscribe"><h3>Новини без шуму</h3>';
        echo '<p>Новинки, поради й спеціальні пропозиції — тільки корисне.</p>';
        echo '<form method="post"><label class="sr-only" for="subscribe-email">Email</label>';
        echo '<input id="subscribe-email" name="maruderm_subscribe_email" type="email" placeholder="Ваш email" autocomplete="email">';
        echo '<button type="submit" aria-label="Підписатися">' . $this->arrowIcon() . '</button></form></div></div>';
        echo '<div class="shell footer-bottom"><span>© ' . esc_html(wp_date('Y')) . ' Maruderm Україна</span><div>';
        echo '<a href="' . esc_url($this->pageUrl(['terms-and-privacy'])) . '">Політика конфіденційності</a>';
        echo '<a href="' . esc_url($this->pageUrl(['public-offer'])) . '">Публічна оферта</a>';
        echo '</div></div></footer>';
    }

    /** @return array<string, string> */
    private function helpLinks(): array
    {
        return [
            'Доставка й оплата' => $this->pageUrl(['dostavka-i-oplata', 'delivery-payment']),
            'Повернення' => $this->pageUrl(['povernennya', 'returns']),
            'Контакти' => $this->pageUrl(['kontakty', 'contacts']),
            'FAQ' => $this->pageUrl(['faq']),
        ];
    }

    /** @param string[] $slugs */
    private function pageUrl(array $slugs): string
    {
        foreach ($slugs as $slug) {
            $page = get_page_by_path($slug);

            if ($page instanceof \WP_Post) {
                return get_permalink($page);
            }
        }

        return home_url('/');
    }

    private function logo(): string
    {
        return '<a class="brand-logo" href="' . esc_url(home_url('/')) . '" aria-label="Maruderm — на головну">'
            . '<span class="brand-logo__word">maru<span>·</span>derm</span>'
            . '<span class="brand-logo__tagline">nature embraces science</span></a>';
    }

    private function arrowIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    }
}
