<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Carrier;

if ( ! defined('ABSPATH')) {
    exit;
}

final class CarrierDefinition
{
    public string $slug;
    public string $name;

    /**
     * Icon file name inside the plugin image directory.
     */
    public string $icon;

    /**
     * Supported features, see CarrierFeature.
     *
     * @var string[]
     */
    public array $features;

    /**
     * @var CarrierOptionGroup[]
     */
    public array $groups;

    /**
     * @param string[] $features
     * @param CarrierOptionGroup[] $groups
     */
    public function __construct(
        string $slug,
        string $name,
        string $icon,
        array $features = [],
        array $groups = []
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->icon = $icon;
        $this->features = $features;
        $this->groups = $groups;
    }

    public function getIconUrl(): string
    {
        return WC_UKR_SHIPPING_PLUGIN_URL . 'image/' . $this->icon;
    }

    public function hasOptions(): bool
    {
        foreach ($this->groups as $group) {
            if ( ! $group->isEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return CarrierOptionField[]
     */
    public function getFields(): array
    {
        $fields = [];

        foreach ($this->groups as $group) {
            foreach ($group->fields as $field) {
                $fields[$field->key] = $field;
            }
        }

        return $fields;
    }
}
