<?php

declare(strict_types=1);

namespace Maruderm\Account;

use Maruderm\Settings\BonusSettings;

if (!defined('ABSPATH')) {
    exit();
}

/** Calculates Maruderm Club balances from paid product revenue. */
final class BonusService
{
    private BonusSettings $settings;

    public function __construct(?BonusSettings $settings = null)
    {
        $this->settings = $settings ?? new BonusSettings();
    }

    /** @return array{points: int, spend: float, tier: string, next_tier: string, remaining: int, progress: float} */
    public function summary(int $userId): array
    {
        $settings = $this->settings->all();
        $spend = $this->eligibleSpend($userId);
        $points = (int) floor($spend * (float) $settings['points_per_uah']);
        $tiers = array_values($settings['tiers']);
        $currentIndex = 0;

        foreach ($tiers as $index => $tier) {
            if ($points >= (int) $tier['threshold']) {
                $currentIndex = $index;
            }
        }

        $current = $tiers[$currentIndex];
        $next = $tiers[$currentIndex + 1] ?? null;
        $currentThreshold = (int) $current['threshold'];
        $nextThreshold = $next !== null ? (int) $next['threshold'] : $currentThreshold;
        $range = max(1, $nextThreshold - $currentThreshold);
        $progress = $next === null ? 100.0 : (($points - $currentThreshold) / $range) * 100;

        return [
            'points' => $points,
            'spend' => $spend,
            'tier' => (string) $current['name'],
            'next_tier' => $next !== null ? (string) $next['name'] : '',
            'remaining' => $next !== null ? max(0, $nextThreshold - $points) : 0,
            'progress' => max(0, min(100, $progress)),
        ];
    }

    public function eligibleSpend(int $userId): float
    {
        if ($userId <= 0 || !function_exists('wc_get_orders')) {
            return 0.0;
        }

        $orderIds = wc_get_orders([
            'customer_id' => $userId,
            'status' => wc_get_is_paid_statuses(),
            'limit' => -1,
            'return' => 'ids',
        ]);
        $spend = 0.0;

        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            foreach ($order->get_items('line_item') as $itemId => $item) {
                $lineNet = (float) $item->get_total() + (float) $item->get_total_tax();
                $refunded = (float) $order->get_total_refunded_for_item($itemId);
                $taxes = $item->get_taxes();

                foreach (array_keys($taxes['total'] ?? []) as $taxId) {
                    $refunded += (float) $order->get_tax_refunded_for_item($itemId, (int) $taxId);
                }

                $spend += max(0, $lineNet - $refunded);
            }
        }

        return (float) wc_format_decimal($spend, wc_get_price_decimals());
    }
}
