<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\SmartyParcel;

interface LabelRequestBuilderInterface
{
    public function build(): array;
}
