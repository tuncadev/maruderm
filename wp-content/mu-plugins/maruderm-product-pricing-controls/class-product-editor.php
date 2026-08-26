<?php
/**
 * Product and variation editor integration.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Product_Pricing_Editor
{
    private Maruderm_Product_Pricing_Policy $policy;
    private Maruderm_Product_Price_Repository $repository;
    private array $rejected_product_ids = [];

    public function __construct(
        Maruderm_Product_Pricing_Policy $policy,
        Maruderm_Product_Price_Repository $repository
    ) {
        $this->policy = $policy;
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('woocommerce_product_options_pricing', [$this, 'render_minimum_field']);
        add_action('woocommerce_variation_options_pricing', [$this, 'render_variation_minimum_field'], 10, 3);
        add_action('woocommerce_process_product_meta', [$this, 'validate_product_request'], 5);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_product_minimum'], 20);
        add_action('woocommerce_admin_process_variation_object', [$this, 'save_variation_minimum'], 20, 2);
        add_action('woocommerce_before_product_object_save', [$this, 'guard_programmatic_save'], 10);
    }

    public function render_minimum_field(): void
    {
        global $product_object;

        if (! $product_object instanceof WC_Product || ! function_exists('woocommerce_wp_text_input')) {
            return;
        }

        woocommerce_wp_text_input([
            'id' => Maruderm_Product_Price_Repository::MINIMUM_PRICE_META,
            'value' => $this->format_nullable($this->repository->minimum($product_object)),
            'label' => 'Minimum sale price (' . get_woocommerce_currency_symbol() . ')',
            'data_type' => 'price',
            'desc_tip' => true,
            'description' => 'Private floor for discounts. It must be at least the cost price; sale price can never be lower.',
            'custom_attributes' => [
                'min' => '0',
                'step' => $this->price_step(),
                'autocomplete' => 'off',
            ],
        ]);
    }

    public function render_variation_minimum_field(int $loop, array $variation_data, WP_Post $variation): void
    {
        $product = wc_get_product($variation->ID);
        if (! $product instanceof WC_Product_Variation || ! function_exists('woocommerce_wp_text_input')) {
            return;
        }

        woocommerce_wp_text_input([
            'id' => 'variable_maruderm_minimum_price_' . $loop,
            'name' => 'variable_maruderm_minimum_price[' . $loop . ']',
            'value' => $this->format_nullable($this->repository->minimum($product)),
            'label' => 'Minimum sale price',
            'data_type' => 'price',
            'desc_tip' => true,
            'description' => 'Private discount floor for this variation.',
            'wrapper_class' => 'form-row form-row-full maruderm-minimum-price-field',
            'custom_attributes' => [
                'min' => '0',
                'step' => $this->price_step(),
                'autocomplete' => 'off',
            ],
        ]);
    }

    public function validate_product_request(int $product_id): void
    {
        if (! isset($_POST['_regular_price'], $_POST['_sale_price'], $_POST[Maruderm_Product_Price_Repository::MINIMUM_PRICE_META])) {
            return;
        }

        $product = wc_get_product($product_id);
        if (! $product instanceof WC_Product) {
            return;
        }

        $cost_raw = $_POST['_cogs_value'] ?? $this->format_nullable($this->repository->cost($product));
        $parsed = $this->parse_values(
            $cost_raw,
            $_POST[Maruderm_Product_Price_Repository::MINIMUM_PRICE_META],
            $_POST['_regular_price'],
            $_POST['_sale_price']
        );

        if (is_wp_error($parsed)) {
            $this->reject_product_request($product, $parsed);
            return;
        }

        $errors = $this->policy->validate($parsed['cost'], $parsed['minimum'], $parsed['regular'], $parsed['sale']);
        if ($errors->has_errors()) {
            $this->reject_product_request($product, $errors);
        }
    }

    public function save_product_minimum(WC_Product $product): void
    {
        if (isset($this->rejected_product_ids[$product->get_id()])) {
            return;
        }

        if (! array_key_exists(Maruderm_Product_Price_Repository::MINIMUM_PRICE_META, $_POST)) {
            return;
        }

        $raw_value = $_POST[Maruderm_Product_Price_Repository::MINIMUM_PRICE_META];
        $minimum = $this->policy->parse_money($raw_value, 'Minimum sale price');
        if (is_wp_error($minimum)) {
            return;
        }

        $this->set_minimum_on_product($product, $minimum);
    }

    public function save_variation_minimum(WC_Product_Variation $variation, int $index): void
    {
        if (! isset($_POST['variable_maruderm_minimum_price'])
            || ! is_array($_POST['variable_maruderm_minimum_price'])
            || ! array_key_exists($index, $_POST['variable_maruderm_minimum_price'])) {
            return;
        }

        $minimum_raw = $_POST['variable_maruderm_minimum_price'][$index];
        $minimum = $this->policy->parse_money($minimum_raw, 'Minimum sale price');
        $cost = $variation->get_cogs_value();
        $regular = $this->nullable_float($variation->get_regular_price('edit'));
        $sale = $this->nullable_float($variation->get_sale_price('edit'));

        if (is_wp_error($minimum)) {
            $this->reject_variation($variation, $minimum);
            return;
        }

        $errors = $this->policy->validate($cost, $minimum, $regular, $sale);
        if ($errors->has_errors()) {
            $this->reject_variation($variation, $errors);
            return;
        }

        $this->set_minimum_on_product($variation, $minimum);
    }

    public function guard_programmatic_save(WC_Product $product): void
    {
        $changes = $product->get_changes();
        if (array_intersect(array_keys($changes), ['regular_price', 'sale_price', 'cogs_value']) === []) {
            return;
        }

        $errors = $this->policy->validate(
            $product->get_cogs_value(),
            $this->repository->minimum($product),
            $this->nullable_float($product->get_regular_price('edit')),
            $this->nullable_float($product->get_sale_price('edit'))
        );

        if ($errors->has_errors()) {
            throw new WC_Data_Exception(
                'maruderm_invalid_product_pricing',
                $this->policy->first_error_message($errors)
            );
        }
    }

    /**
     * @return array{cost: ?float, minimum: ?float, regular: ?float, sale: ?float}|WP_Error
     */
    private function parse_values($cost_raw, $minimum_raw, $regular_raw, $sale_raw)
    {
        $fields = [
            'cost' => [$cost_raw, 'Cost price'],
            'minimum' => [$minimum_raw, 'Minimum sale price'],
            'regular' => [$regular_raw, 'Regular price'],
            'sale' => [$sale_raw, 'Sale price'],
        ];
        $values = [];

        foreach ($fields as $key => [$raw_value, $label]) {
            $value = $this->policy->parse_money($raw_value, $label);
            if (is_wp_error($value)) {
                return $value;
            }
            $values[$key] = $value;
        }

        return $values;
    }

    private function reject_product_request(WC_Product $product, WP_Error $errors): void
    {
        $this->rejected_product_ids[$product->get_id()] = true;
        $this->restore_main_request($product);
        WC_Admin_Meta_Boxes::add_error('Pricing changes were not saved: ' . $this->policy->first_error_message($errors));
    }

    private function restore_main_request(WC_Product $product): void
    {
        $_POST['_regular_price'] = $product->get_regular_price('edit');
        $_POST['_sale_price'] = $product->get_sale_price('edit');
        $_POST['_cogs_value'] = $this->format_nullable($this->repository->cost($product));
        $_POST[Maruderm_Product_Price_Repository::MINIMUM_PRICE_META] = $this->format_nullable($this->repository->minimum($product));
        $_POST['_sale_price_dates_from'] = $this->format_date($product->get_date_on_sale_from('edit'));
        $_POST['_sale_price_dates_to'] = $this->format_date($product->get_date_on_sale_to('edit'));
    }

    private function reject_variation(WC_Product_Variation $variation, WP_Error $errors): void
    {
        $persisted = new WC_Product_Variation($variation->get_id());
        $variation->set_regular_price($persisted->get_regular_price('edit'));
        $variation->set_sale_price($persisted->get_sale_price('edit'));
        $variation->set_price($persisted->get_price('edit'));
        $variation->set_date_on_sale_from($persisted->get_date_on_sale_from('edit'));
        $variation->set_date_on_sale_to($persisted->get_date_on_sale_to('edit'));
        $variation->set_cogs_value($persisted->get_cogs_value());
        WC_Admin_Meta_Boxes::add_error(
            sprintf(
                'Variation #%d pricing changes were not saved: %s',
                $variation->get_id(),
                $this->policy->first_error_message($errors)
            )
        );
    }

    private function set_minimum_on_product(WC_Product $product, ?float $minimum): void
    {
        if ($minimum === null) {
            $product->delete_meta_data(Maruderm_Product_Price_Repository::MINIMUM_PRICE_META);
            return;
        }

        $product->update_meta_data(
            Maruderm_Product_Price_Repository::MINIMUM_PRICE_META,
            wc_format_decimal($minimum, wc_get_price_decimals())
        );
    }

    private function nullable_float($value): ?float
    {
        return $value === '' || $value === null ? null : (float) $value;
    }

    private function format_nullable(?float $value): string
    {
        return $value === null ? '' : wc_format_decimal($value, wc_get_price_decimals());
    }

    private function format_date($date): string
    {
        return $date instanceof WC_DateTime ? $date->date('Y-m-d') : '';
    }

    private function price_step(): string
    {
        return wc_get_price_decimals() > 0 ? '0.' . str_repeat('0', wc_get_price_decimals() - 1) . '1' : '1';
    }
}
