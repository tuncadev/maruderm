<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Component\Carriers\CarrierCatalog;
use kirillbdev\WCUkrShipping\Dto\Carrier\CarrierDefinition;
use kirillbdev\WCUkrShipping\Dto\Carrier\CarrierOptionField;
use kirillbdev\WCUkrShipping\Enums\CarrierFeature;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Carriers enabled for the store and their own options.
 */
class CarrierService
{
    private const ACTIVE_CARRIERS_OPTION = 'wcus_active_carriers';

    private CarrierCatalog $catalog;

    public function __construct(CarrierCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function find(string $slug): ?CarrierDefinition
    {
        return $this->catalog->find($slug);
    }

    /**
     * Carrier rows of the settings page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCarrierList(): array
    {
        $activeCarriers = $this->getActiveSlugs();
        $rows = [];

        foreach ($this->catalog->all() as $carrier) {
            $rows[] = [
                'slug' => $carrier->slug,
                'name' => $carrier->name,
                'icon' => $carrier->getIconUrl(),
                'features' => $this->mapFeatures($carrier->features),
                'enabled' => in_array($carrier->slug, $activeCarriers, true),
                'hasOptions' => $carrier->hasOptions(),
            ];
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    public function getActiveSlugs(): array
    {
        return WCUSHelper::safeGetJsonOption(self::ACTIVE_CARRIERS_OPTION);
    }

    public function setActive(CarrierDefinition $carrier, bool $active): void
    {
        $activeCarriers = $this->getActiveSlugs();

        if ($active) {
            if ( ! in_array($carrier->slug, $activeCarriers, true)) {
                $activeCarriers[] = $carrier->slug;
            }
        } else {
            $activeCarriers = array_filter($activeCarriers, static function ($slug) use ($carrier) {
                return $slug !== $carrier->slug;
            });
        }

        update_option(self::ACTIVE_CARRIERS_OPTION, json_encode(array_values($activeCarriers)));

        $this->flushShippingCache();
    }

    /**
     * Option groups of the carrier with the current values.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOptions(CarrierDefinition $carrier): array
    {
        $groups = [];

        foreach ($carrier->groups as $group) {
            $fields = [];

            foreach ($group->fields as $field) {
                $fields[] = [
                    'key' => $field->key,
                    'type' => $field->type,
                    'label' => $field->label,
                    'tooltip' => $field->tooltip,
                    'options' => $this->mapSelectOptions($field),
                    'value' => $this->readValue($field),
                ];
            }

            $groups[] = [
                'title' => $group->title,
                'fields' => $fields,
                'widget' => $group->widget,
            ];
        }

        return $groups;
    }

    /**
     * Saves only the options declared by the carrier, any other key is ignored.
     *
     * @param array<string, mixed> $values
     */
    public function saveOptions(CarrierDefinition $carrier, array $values): void
    {
        foreach ($carrier->getFields() as $key => $field) {
            if ( ! array_key_exists($key, $values)) {
                continue;
            }

            update_option($key, $this->sanitizeValue($field, $values[$key]));
        }

        $this->flushShippingCache();
    }

    /**
     * Human readable labels of the supported features, in the declared order.
     *
     * @param string[] $features
     *
     * @return string[]
     */
    private function mapFeatures(array $features): array
    {
        $labels = [
            CarrierFeature::PICKUP_POINTS => __('Pickup points', 'wc-ukr-shipping'),
            CarrierFeature::ADDRESS_DELIVERY => __('Address delivery', 'wc-ukr-shipping'),
            CarrierFeature::RATE_CALCULATION => __('Rates calculation', 'wc-ukr-shipping'),
            CarrierFeature::SHIPPING_LABELS => __('Shipping labels', 'wc-ukr-shipping'),
            CarrierFeature::TRACKING => __('Tracking', 'wc-ukr-shipping'),
        ];

        $result = [];

        foreach ($features as $feature) {
            if (isset($labels[$feature])) {
                $result[] = $labels[$feature];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, string>>|null
     */
    private function mapSelectOptions(CarrierOptionField $field): ?array
    {
        if ( ! $field->isSelect()) {
            return null;
        }

        $options = [];

        foreach ($field->options as $value => $label) {
            $options[] = [
                'value' => (string)$value,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @return int|string
     */
    private function readValue(CarrierOptionField $field)
    {
        $value = wc_ukr_shipping_get_option($field->key) ?? $field->default;

        return $field->isSwitcher() ? (int)((int)$value === 1) : (string)$value;
    }

    /**
     * @param mixed $value
     *
     * @return int|string
     */
    private function sanitizeValue(CarrierOptionField $field, $value)
    {
        if ($field->isSwitcher()) {
            return (int)((int)$value === 1);
        }

        $value = sanitize_text_field(wp_unslash((string)$value));

        if ($field->isSelect() && ! array_key_exists($value, $field->options)) {
            return (string)($field->default ?? array_key_first($field->options));
        }

        return $value;
    }

    private function flushShippingCache(): void
    {
        delete_option('_transient_shipping-transient-version');
    }
}
