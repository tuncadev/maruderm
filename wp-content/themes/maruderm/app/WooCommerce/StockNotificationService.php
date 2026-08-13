<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Stores authenticated customer stock subscriptions in WordPress user metadata.
 */
final class StockNotificationService
{
    private const INDEX_META_KEY = '_maruderm_stock_notification_product_ids';
    private const PRODUCT_META_PREFIX = '_maruderm_stock_notification_';

    /** @var array<string, bool> */
    private array $subscription_cache = [];

    public function isAvailable(): bool
    {
        return function_exists('wc_get_product');
    }

    public function isSubscribed(int $product_id, int $user_id): bool
    {
        if (!$this->isAvailable() || $product_id <= 0 || $user_id <= 0) {
            return false;
        }

        $cache_key = $product_id . ':' . $user_id;

        if (!array_key_exists($cache_key, $this->subscription_cache)) {
            $this->subscription_cache[$cache_key] = get_user_meta(
                $user_id,
                $this->productMetaKey($product_id),
                true
            ) === 'yes';
        }

        return $this->subscription_cache[$cache_key];
    }

    /**
     * @return bool|\WP_Error True when subscribed, false when unsubscribed.
     */
    public function toggle(int $product_id, int $user_id)
    {
        if (!$this->isAvailable()) {
            return new \WP_Error('stock_notifications_unavailable', 'Сервіс сповіщень тимчасово недоступний.');
        }

        if ($product_id <= 0 || $user_id <= 0) {
            return new \WP_Error('stock_notifications_invalid_request', 'Не вдалося обробити запит.');
        }

        if ($this->isSubscribed($product_id, $user_id)) {
            $this->removeSubscription($product_id, $user_id);

            return false;
        }

        $user = get_user_by('id', $user_id);
        $product = wc_get_product($product_id);

        if (!$user instanceof \WP_User) {
            return new \WP_Error('stock_notifications_invalid_user', 'Увійди в акаунт, щоб увімкнути сповіщення.');
        }

        if (!$product instanceof \WC_Product || $product->get_status() !== 'publish' || $product->is_in_stock()) {
            return new \WP_Error(
                'stock_notifications_invalid_product',
                'Цей товар зараз не можна додати до списку сповіщень.'
            );
        }

        update_user_meta($user_id, $this->productMetaKey($product_id), 'yes');
        $product_ids = $this->productIdsForUser($user_id);

        if (!in_array($product_id, $product_ids, true)) {
            $product_ids[] = $product_id;
            update_user_meta($user_id, self::INDEX_META_KEY, $product_ids);
        }

        $this->subscription_cache[$product_id . ':' . $user_id] = true;

        return true;
    }

    /** @return \WC_Product[] */
    public function subscriptionsForUser(int $user_id): array
    {
        $products = [];

        foreach (array_reverse($this->productIdsForUser($user_id)) as $product_id) {
            if (!$this->isSubscribed($product_id, $user_id)) {
                continue;
            }

            $product = wc_get_product($product_id);

            if ($product instanceof \WC_Product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /** @return int[] */
    public function subscribedUserIds(int $product_id): array
    {
        if ($product_id <= 0) {
            return [];
        }

        $query = new \WP_User_Query([
            'fields' => 'ids',
            'number' => -1,
            'meta_key' => $this->productMetaKey($product_id),
            'meta_value' => 'yes',
        ]);

        return array_values(array_filter(array_map('absint', $query->get_results())));
    }

    public function removeSubscription(int $product_id, int $user_id): void
    {
        delete_user_meta($user_id, $this->productMetaKey($product_id));
        $product_ids = array_values(array_filter(
            $this->productIdsForUser($user_id),
            static fn(int $stored_product_id): bool => $stored_product_id !== $product_id
        ));
        update_user_meta($user_id, self::INDEX_META_KEY, $product_ids);
        $this->subscription_cache[$product_id . ':' . $user_id] = false;
    }

    /** @return int[] */
    private function productIdsForUser(int $user_id): array
    {
        $product_ids = get_user_meta($user_id, self::INDEX_META_KEY, true);

        if (!is_array($product_ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('absint', $product_ids))));
    }

    private function productMetaKey(int $product_id): string
    {
        return self::PRODUCT_META_PREFIX . $product_id;
    }
}
