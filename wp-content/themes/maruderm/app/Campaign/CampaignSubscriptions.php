<?php

declare(strict_types=1);

namespace Maruderm\Campaign;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Persists campaign consent, impressions and single-use welcome coupons in WordPress. */
final class CampaignSubscriptions implements Registrable
{
    use Loadable;

    private const POST_TYPE = 'maru_subscriber';
    private const NONCE_ACTION = 'maruderm-campaign';
    private const COOKIE_NAME = 'maruderm_campaign_last_shown_at';
    private const USER_META = '_maruderm_campaign_last_shown_at';
    private const FREQUENCY_SECONDS = DAY_IN_SECONDS;

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('wp_ajax_maruderm_campaign_impression', [$this, 'recordImpression']);
        add_action('wp_ajax_nopriv_maruderm_campaign_impression', [$this, 'recordImpression']);
        add_action('wp_ajax_maruderm_campaign_subscribe', [$this, 'subscribe']);
        add_action('wp_ajax_nopriv_maruderm_campaign_subscribe', [$this, 'subscribe']);
    }

    public function registerPostType(): void
    {
        $capabilities = array_fill_keys([
            'edit_post',
            'read_post',
            'delete_post',
            'edit_posts',
            'edit_others_posts',
            'publish_posts',
            'read_private_posts',
            'delete_posts',
            'delete_private_posts',
            'delete_published_posts',
            'delete_others_posts',
            'edit_private_posts',
            'edit_published_posts',
            'create_posts',
        ], 'manage_woocommerce');

        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Підписники кампаній',
                'singular_name' => 'Підписник кампанії',
                'menu_name' => 'Підписники кампаній',
                'add_new_item' => 'Додати підписника',
                'edit_item' => 'Редагувати підписника',
                'search_items' => 'Пошук підписників',
                'not_found' => 'Підписників не знайдено',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'woocommerce',
            'show_in_rest' => false,
            'supports' => ['title'],
            'map_meta_cap' => false,
            'capabilities' => $capabilities,
        ]);
    }

    /** @return array<string, int|string> */
    public static function clientConfig(): array
    {
        return [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'frequencyMs' => self::FREQUENCY_SECONDS * 1000,
            'lastShownAt' => self::lastShownAt() * 1000,
            'cookieName' => self::COOKIE_NAME,
            'impressionAction' => 'maruderm_campaign_impression',
            'subscribeAction' => 'maruderm_campaign_subscribe',
        ];
    }

    public function recordImpression(): void
    {
        $this->verifyRequest();
        $campaign_id = sanitize_key(wp_unslash((string) ($_POST['campaign_id'] ?? '')));

        if (CampaignPopupContent::byId($campaign_id) === null) {
            wp_send_json_error(['message' => 'Невідома кампанія.'], 400);
        }

        $timestamp = time();

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), self::USER_META, $timestamp);
        }

        self::setFrequencyCookie($timestamp);
        wp_send_json_success(['lastShownAt' => $timestamp * 1000]);
    }

    public function subscribe(): void
    {
        $this->verifyRequest();
        $campaign_id = sanitize_key(wp_unslash((string) ($_POST['campaign_id'] ?? '')));
        $campaign = CampaignPopupContent::byId($campaign_id);
        $email = sanitize_email(wp_unslash((string) ($_POST['email'] ?? '')));

        if (!is_array($campaign)) {
            wp_send_json_error(['message' => 'Невідома кампанія.'], 400);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Перевір формат email.'], 422);
        }

        if ($this->isRateLimited()) {
            wp_send_json_error(['message' => 'Зачекай хвилину й спробуй ще раз.'], 429);
        }

        $subscriber_id = $this->findSubscriber($email);
        $is_new = $subscriber_id <= 0;

        if ($is_new) {
            $subscriber_id = wp_insert_post([
                'post_type' => self::POST_TYPE,
                'post_status' => 'private',
                'post_title' => $email,
            ], true);

            if (is_wp_error($subscriber_id)) {
                wp_send_json_error(['message' => 'Не вдалося зберегти підписку. Спробуй ще раз.'], 500);
            }

            update_post_meta($subscriber_id, '_maruderm_subscriber_email', $email);
            update_post_meta($subscriber_id, '_maruderm_subscriber_email_hash', hash('sha256', strtolower($email)));
            update_post_meta($subscriber_id, '_maruderm_subscriber_consent_at', gmdate('c'));
            update_post_meta($subscriber_id, '_maruderm_subscriber_source_url', esc_url_raw(wp_get_referer() ?: home_url('/')));
            update_post_meta($subscriber_id, '_maruderm_subscriber_ip_hash', $this->clientHash());
            update_post_meta($subscriber_id, '_maruderm_subscriber_user_id', get_current_user_id());
        }

        update_post_meta($subscriber_id, '_maruderm_subscriber_campaign', $campaign_id);
        update_post_meta($subscriber_id, '_maruderm_subscriber_updated_at', gmdate('c'));

        $coupon_code = (string) get_post_meta($subscriber_id, '_maruderm_subscriber_coupon', true);

        if ($coupon_code === '') {
            $coupon_code = $this->createWelcomeCoupon($email, $campaign_id);

            if ($coupon_code !== '') {
                update_post_meta($subscriber_id, '_maruderm_subscriber_coupon', $coupon_code);
            }
        }

        $email_sent = $this->sendWelcomeEmail($email, $coupon_code, $is_new);
        update_post_meta($subscriber_id, '_maruderm_subscriber_last_email_sent', $email_sent ? gmdate('c') : 'failed');

        wp_send_json_success([
            'message' => (string) $campaign['success_message'],
            'emailSent' => $email_sent,
        ]);
    }

    private function verifyRequest(): void
    {
        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => 'Сесію завершено. Онови сторінку й спробуй ще раз.'], 403);
        }
    }

    private function isRateLimited(): bool
    {
        $key = 'maruderm_campaign_' . substr($this->clientHash(), 0, 32);

        if (get_transient($key)) {
            return true;
        }

        set_transient($key, 1, MINUTE_IN_SECONDS);

        return false;
    }

    private function clientHash(): string
    {
        $ip = sanitize_text_field(wp_unslash((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')));
        $agent = sanitize_text_field(wp_unslash((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')));

        return hash_hmac('sha256', $ip . '|' . $agent, wp_salt('auth'));
    }

    private function findSubscriber(string $email): int
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_key' => '_maruderm_subscriber_email_hash',
            'meta_value' => hash('sha256', strtolower($email)),
        ]);

        return isset($posts[0]) ? (int) $posts[0] : 0;
    }

    private function createWelcomeCoupon(string $email, string $campaign_id): string
    {
        if (!class_exists('WC_Coupon')) {
            return '';
        }

        do {
            $code = 'MARU10-' . strtoupper(wp_generate_password(8, false, false));
        } while (wc_get_coupon_id_by_code($code) > 0);

        $coupon = new \WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_description('Welcome campaign: ' . $campaign_id);
        $coupon->set_discount_type('percent');
        $coupon->set_amount(10);
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);
        $coupon->set_email_restrictions([$email]);
        $coupon->set_date_expires(time() + (30 * DAY_IN_SECONDS));
        $coupon->save();

        return $code;
    }

    private function sendWelcomeEmail(string $email, string $coupon_code, bool $is_new): bool
    {
        $subject = $is_new ? 'Welcome to Maruderm Ukraine' : 'Твоя підписка Maruderm Ukraine';
        $body = "Дякуємо, що приєдналася до Maruderm Ukraine.\n\n";

        if ($coupon_code !== '') {
            $body .= "Твій персональний welcome-код на -10%: {$coupon_code}\n";
            $body .= "Код діє 30 днів і доступний для одного замовлення.\n\n";
        }

        $body .= 'Каталог: ' . wc_get_page_permalink('shop') . "\n";
        $body .= 'Політика конфіденційності: ' . self::privacyUrl() . "\n";

        return wp_mail($email, $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);
    }

    private static function privacyUrl(): string
    {
        $page = get_page_by_path('terms-and-privacy');

        return $page instanceof \WP_Post ? get_permalink($page) : home_url('/terms-and-privacy/');
    }

    private static function lastShownAt(): int
    {
        if (is_user_logged_in()) {
            return absint(get_user_meta(get_current_user_id(), self::USER_META, true));
        }

        return absint(wp_unslash((string) ($_COOKIE[self::COOKIE_NAME] ?? '0')));
    }

    private static function setFrequencyCookie(int $timestamp): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE_NAME, (string) $timestamp, [
            'expires' => $timestamp + self::FREQUENCY_SECONDS,
            'path' => COOKIEPATH ?: '/',
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
}
