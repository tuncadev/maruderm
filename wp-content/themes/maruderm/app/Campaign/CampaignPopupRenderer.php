<?php

declare(strict_types=1);

namespace Maruderm\Campaign;

use Maruderm\Kernel\Helpers;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the canonical campaign dialog with live theme-owned media URLs. */
final class CampaignPopupRenderer
{
    public static function isCurrent(): bool
    {
        return CampaignPopupContent::currentKey() !== null;
    }

    public function render(): void
    {
        $campaign = CampaignPopupContent::current();

        if (!is_array($campaign)) {
            return;
        }

        $id = (string) $campaign['id'];
        $video_url = Helpers::theme_uri('assets/campaign-popup/videos/' . (string) $campaign['video']);

        echo '<svg hidden aria-hidden="true"><symbol id="campaign-icon-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"></path></symbol><symbol id="campaign-icon-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"></path></symbol></svg>';
        echo '<div class="campaign-popup" data-campaign-popup data-campaign-id="' . esc_attr($id) . '" data-campaign-delay="' . esc_attr((string) $campaign['delay']) . '" data-campaign-tone="' . esc_attr((string) $campaign['tone']) . '" hidden>';
        echo '<button class="campaign-popup__backdrop" type="button" aria-label="Закрити пропозицію" data-campaign-close></button>';
        echo '<section class="campaign-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="' . esc_attr($id) . '-title" aria-describedby="' . esc_attr($id) . '-description">';
        echo '<div class="campaign-popup__media" aria-hidden="true"><video muted loop playsinline preload="metadata" data-campaign-video><source src="' . esc_url($video_url) . '" type="video/webm"></video>';
        echo '<div class="campaign-popup__media-topline"><span>maru·derm</span><span>ukraine launch / 2026</span></div>';
        echo '<div class="campaign-popup__stat"><strong>' . esc_html((string) $campaign['stat']) . '</strong><span>' . esc_html((string) $campaign['stat_label']) . '</span></div>';
        echo '<p>' . esc_html((string) $campaign['media_note']) . '</p></div>';
        echo '<div class="campaign-popup__content"><button class="campaign-popup__close" type="button" aria-label="Закрити пропозицію" data-campaign-close><svg><use href="#campaign-icon-close"></use></svg></button>';
        echo '<span class="campaign-popup__eyebrow">' . esc_html((string) $campaign['eyebrow']) . '</span><h2 id="' . esc_attr($id) . '-title">' . esc_html((string) $campaign['title']) . '</h2>';
        echo '<p id="' . esc_attr($id) . '-description" class="campaign-popup__description">' . esc_html((string) $campaign['description']) . '</p>';
        echo '<ul class="campaign-popup__benefits" aria-label="Переваги підписки">';

        foreach ((array) $campaign['benefits'] as $benefit) {
            echo '<li><span aria-hidden="true">✓</span>' . esc_html((string) $benefit) . '</li>';
        }

        echo '</ul><form class="campaign-popup__form" data-campaign-form novalidate><label class="sr-only" for="' . esc_attr($id) . '-email">Email для підписки</label>';
        echo '<input id="' . esc_attr($id) . '-email" type="email" name="email" inputmode="email" autocomplete="email" placeholder="Твій email" required data-campaign-email>';
        echo '<button type="submit" data-campaign-submit><span>' . esc_html((string) $campaign['submit_label']) . '</span><svg><use href="#campaign-icon-arrow"></use></svg></button></form>';
        echo '<p class="campaign-popup__status" aria-live="polite" data-campaign-status data-success-message="' . esc_attr((string) $campaign['success_message']) . '"></p>';
        echo '<small class="campaign-popup__privacy">Надсилаємо лише новини, пропозиції та корисні beauty-нотатки. Без спаму.</small></div></section></div>';
    }
}
