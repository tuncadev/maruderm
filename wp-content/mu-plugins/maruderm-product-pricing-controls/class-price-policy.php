<?php
/**
 * Product pricing validation policy.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Product_Pricing_Policy
{
    /**
     * @return float|null|WP_Error
     */
    public function parse_money($raw_value, string $label)
    {
        if ($raw_value === null || trim((string) $raw_value) === '') {
            return null;
        }

        $value = wc_format_decimal(wp_unslash((string) $raw_value));
        if ($value === '' || ! is_numeric($value)) {
            return new WP_Error('invalid_money', sprintf('%s must be a valid amount.', $label));
        }

        $amount = (float) $value;
        if ($amount < 0) {
            return new WP_Error('negative_money', sprintf('%s cannot be negative.', $label));
        }

        return $amount;
    }

    public function validate(?float $cost, ?float $minimum, ?float $regular, ?float $sale): WP_Error
    {
        $errors = new WP_Error();

        if (($cost === null) !== ($minimum === null)) {
            $errors->add(
                'incomplete_private_prices',
                'Cost price and minimum sale price must either both be entered or both be empty.'
            );
        }

        if ($cost !== null && $minimum !== null && $this->less_than($minimum, $cost)) {
            $errors->add('minimum_below_cost', 'Minimum sale price cannot be lower than cost price.');
        }

        if ($minimum !== null && $regular !== null && $this->greater_than($minimum, $regular)) {
            $errors->add('minimum_above_regular', 'Minimum sale price cannot be higher than regular price.');
        }

        if ($sale === null) {
            return $errors;
        }

        if ($cost === null || $minimum === null) {
            $errors->add(
                'sale_without_private_prices',
                'Enter cost price and minimum sale price before setting a sale price.'
            );
        }

        if ($regular === null) {
            $errors->add('sale_without_regular', 'Enter a regular price before setting a sale price.');
        } elseif (! $this->less_than($sale, $regular)) {
            $errors->add('sale_not_below_regular', 'Sale price must be lower than regular price.');
        }

        if ($cost !== null && $this->less_than($sale, $cost)) {
            $errors->add('sale_below_cost', 'Sale price cannot be lower than cost price.');
        }

        if ($minimum !== null && $this->less_than($sale, $minimum)) {
            $errors->add('sale_below_minimum', 'Sale price cannot be lower than minimum sale price.');
        }

        return $errors;
    }

    public function first_error_message(WP_Error $errors): string
    {
        $messages = $errors->get_error_messages();

        return $messages === [] ? '' : (string) $messages[0];
    }

    private function less_than(float $left, float $right): bool
    {
        return $left < $right - $this->epsilon();
    }

    private function greater_than(float $left, float $right): bool
    {
        return $left > $right + $this->epsilon();
    }

    private function epsilon(): float
    {
        return 1 / (10 ** max(0, wc_get_price_decimals()));
    }
}
