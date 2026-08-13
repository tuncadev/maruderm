<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Queues and sends WooCommerce-styled back-in-stock messages.
 */
final class StockNotificationMailer
{
    public const PROCESS_ACTION = 'maruderm_process_stock_notifications';
    private const ACTION_GROUP = 'maruderm-stock-notifications';

    private StockNotificationService $service;

    public function __construct(?StockNotificationService $service = null)
    {
        $this->service = $service ?? new StockNotificationService();
    }

    public function schedule(int $product_id, string $stock_status, \WC_Product $product): void
    {
        if (!in_array($stock_status, ['instock', 'onbackorder'], true)) {
            return;
        }

        $args = ['product_id' => $product_id];

        if (function_exists('as_enqueue_async_action')) {
            if (function_exists('as_has_scheduled_action')
                && as_has_scheduled_action(self::PROCESS_ACTION, $args, self::ACTION_GROUP)) {
                return;
            }

            as_enqueue_async_action(self::PROCESS_ACTION, $args, self::ACTION_GROUP, true);

            return;
        }

        wp_schedule_single_event(time() + 1, self::PROCESS_ACTION, [$product_id]);
    }

    public function sendForProduct(int $product_id): void
    {
        $product = wc_get_product($product_id);

        if (!$product instanceof \WC_Product || !$product->is_in_stock()) {
            return;
        }

        foreach ($this->service->subscribedUserIds($product_id) as $user_id) {
            $user = get_user_by('id', $user_id);

            if (!$user instanceof \WP_User || !is_email($user->user_email)) {
                continue;
            }

            $sent = $this->send($product, $user);

            if ($sent) {
                $this->service->removeSubscription($product_id, $user_id);
            } else {
                wc_get_logger()->error(
                    sprintf('Could not send stock notification for product %d to user %d.', $product_id, $user_id),
                    ['source' => 'maruderm-stock-notifications']
                );
            }

            do_action('maruderm_stock_notification_email_sent', $product, $user, $sent);
        }
    }

    private function send(\WC_Product $product, \WP_User $user): bool
    {
        $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf('[%s] %s знову в наявності', $site_name, $product->get_name());
        $heading = 'Товар знову в наявності';
        $account_url = wc_get_account_endpoint_url('stock-notifications');
        $product_url = $product->get_permalink();
        $message = sprintf(
            '<p>Вітаємо, %1$s!</p><p><strong>%2$s</strong> знову можна замовити.</p>%3$s<p><a href="%4$s">Переглянути товар</a></p><p>Керувати сповіщеннями можна у розділі <a href="%5$s">«Сповіщення про наявність»</a> твого акаунта.</p>',
            esc_html($user->display_name !== '' ? $user->display_name : $user->user_login),
            esc_html($product->get_name()),
            wp_kses_post($product->get_image('woocommerce_thumbnail')),
            esc_url($product_url),
            esc_url($account_url)
        );

        $subject = (string) apply_filters('maruderm_stock_notification_email_subject', $subject, $product, $user);
        $heading = (string) apply_filters('maruderm_stock_notification_email_heading', $heading, $product, $user);
        $message = (string) apply_filters('maruderm_stock_notification_email_content', $message, $product, $user);

        $mailer = WC()->mailer();
        $wrapped_message = $mailer->wrap_message($heading, $message);

        return $mailer->send(
            $user->user_email,
            $subject,
            $wrapped_message,
            "Content-Type: text/html; charset=UTF-8\r\n"
        );
    }
}
