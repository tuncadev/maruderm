<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Exceptions\SmartyParcel;

class SmartyParcelErrorException extends \Exception
{
    private array $details;

    public function __construct(
        int $code,
        string $message,
        array $details = []
    ) {
        parent::__construct($message, $code);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function hasDetails(): bool
    {
        return count($this->details) > 0;
    }
}
