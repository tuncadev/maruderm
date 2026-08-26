<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Carrier;

if ( ! defined('ABSPATH')) {
    exit;
}

final class CarrierOptionGroup
{
    /**
     * Frontend widget rendered inside the group in addition to its fields.
     */
    public const WIDGET_NP_WAREHOUSE_LOADER = 'np_warehouse_loader';

    public string $title;

    /**
     * @var CarrierOptionField[]
     */
    public array $fields;

    public ?string $widget;

    /**
     * @param CarrierOptionField[] $fields
     */
    public function __construct(string $title, array $fields = [], ?string $widget = null)
    {
        $this->title = $title;
        $this->fields = $fields;
        $this->widget = $widget;
    }

    public function isEmpty(): bool
    {
        return $this->fields === [] && $this->widget === null;
    }
}
