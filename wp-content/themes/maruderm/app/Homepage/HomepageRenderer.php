<?php

declare(strict_types=1);

namespace Maruderm\Homepage;

use Maruderm\LandingPage\LandingPageRenderer;

if (!defined('ABSPATH')) {
    exit();
}

final class HomepageRenderer
{
    private LandingPageRenderer $renderer;

    public function __construct(?LandingPageRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new LandingPageRenderer();
    }

    public function render(): void
    {
        $this->renderer->renderHomepage();
    }
}
