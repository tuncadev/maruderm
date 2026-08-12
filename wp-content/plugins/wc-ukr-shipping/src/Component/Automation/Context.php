<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation;

if ( ! defined('ABSPATH')) {
    exit;
}

class Context
{
    private string $event;
    private \WC_Order $order;
    private array $label;

    public function __construct(string $event, \WC_Order $order, array $label)
    {
        $this->event = $event;
        $this->order = $order;
        $this->label = $label;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getOrder(): \WC_Order
    {
        return $this->order;
    }

    public function getLabel(): array
    {
        return $this->label;
    }
}
