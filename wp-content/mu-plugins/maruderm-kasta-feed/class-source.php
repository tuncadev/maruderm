<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

interface Source
{
    public function collect(): array;
}
