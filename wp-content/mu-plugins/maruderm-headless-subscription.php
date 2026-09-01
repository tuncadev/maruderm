<?php
/**
 * Controlled Mailchimp subscription bridge for the headless storefront.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Headless_Subscription
{
    private const NAMESPACE = 'maruderm/v1';
    private const ROUTE = '/subscription';
    private const RATE_LIMIT = 5;
    private const RATE_WINDOW = HOUR_IN_SECONDS;

    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register_route']);
    }

    public static function register_route(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => '__return_true',
            'callback' => [self::class, 'subscribe'],
            'args' => [
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'marketingConsent' => [
                    'required' => true,
                    'type' => 'boolean',
                ],
                'locale' => [
                    'type' => 'string',
                    'default' => 'uk',
                    'sanitize_callback' => 'sanitize_key',
                ],
                'company' => [
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public static function subscribe(WP_REST_Request $request): WP_REST_Response
    {
        if ((string) $request->get_param('company') !== '') {
            return self::response('pending_confirmation', 202);
        }

        $email = sanitize_email((string) $request->get_param('email'));

        if ($email === '' || ! is_email($email)) {
            return self::response('invalid_email', 422);
        }

        if ($request->get_param('marketingConsent') !== true) {
            return self::response('consent_required', 422);
        }

        if (! self::consume_rate_limit()) {
            return self::response('rate_limited', 429);
        }

        $audience_id = (string) get_option('maruderm_mailchimp_audience_id', '');

        if ($audience_id === '' || ! class_exists('MC4WP_MailChimp')) {
            return self::response('service_unavailable', 503);
        }

        $mailchimp = new MC4WP_MailChimp();
        $subscriber = $mailchimp->list_subscribe($audience_id, $email, [
            'status' => 'pending',
            'language' => self::normalise_locale((string) $request->get_param('locale')),
            'tags' => ['next-footer', 'consent-v1'],
        ]);

        if ($subscriber !== null) {
            return self::response('pending_confirmation', 202);
        }

        if ((int) $mailchimp->error_code === 214) {
            return self::response('already_subscribed', 200);
        }

        return self::response('provider_error', 502);
    }

    private static function consume_rate_limit(): bool
    {
        $address = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
        $key = 'maruderm_sub_' . substr(hash('sha256', wp_salt('nonce') . '|' . $address), 0, 32);
        $attempts = (int) get_transient($key);

        if ($attempts >= self::RATE_LIMIT) {
            return false;
        }

        set_transient($key, $attempts + 1, self::RATE_WINDOW);
        return true;
    }

    private static function normalise_locale(string $locale): string
    {
        $language = strtolower(substr($locale, 0, 2));
        return in_array($language, ['uk', 'en'], true) ? $language : 'uk';
    }

    private static function response(string $code, int $status): WP_REST_Response
    {
        return new WP_REST_Response(['code' => $code], $status);
    }
}

Maruderm_Headless_Subscription::init();
