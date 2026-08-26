<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Carrier;

if ( ! defined('ABSPATH')) {
    exit;
}

final class CarrierOptionField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_SELECT = 'select';
    public const TYPE_SWITCHER = 'switcher';

    public string $key;
    public string $type;
    public string $label;
    public ?string $tooltip;

    /**
     * Available values of the select field, indexed by option value.
     *
     * @var array<string, string>
     */
    public array $options;

    public ?string $default;

    /**
     * @param array<string, string> $options
     */
    public function __construct(
        string $key,
        string $type,
        string $label,
        ?string $tooltip = null,
        array $options = [],
        ?string $default = null
    ) {
        $this->key = $key;
        $this->type = $type;
        $this->label = $label;
        $this->tooltip = $tooltip;
        $this->options = $options;
        $this->default = $default;
    }

    public static function text(string $key, string $label, ?string $tooltip = null): self
    {
        return new self($key, self::TYPE_TEXT, $label, $tooltip);
    }

    public static function switcher(string $key, string $label, ?string $tooltip = null, bool $default = false): self
    {
        return new self($key, self::TYPE_SWITCHER, $label, $tooltip, [], $default ? '1' : '0');
    }

    /**
     * @param array<string, string> $options
     */
    public static function select(
        string $key,
        string $label,
        array $options,
        ?string $default = null,
        ?string $tooltip = null
    ): self {
        return new self($key, self::TYPE_SELECT, $label, $tooltip, $options, $default);
    }

    public function isSwitcher(): bool
    {
        return $this->type === self::TYPE_SWITCHER;
    }

    public function isSelect(): bool
    {
        return $this->type === self::TYPE_SELECT;
    }
}
