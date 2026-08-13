<?php

declare(strict_types=1);

namespace Maruderm\Homepage;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Removes parent-theme shells superseded by the canonical homepage layout. */
final class HomepagePage implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_action('wp', [$this, 'removeInheritedLayout'], 20);
    }

    public function removeInheritedLayout(): void
    {
        if (!is_front_page()) {
            return;
        }

        remove_action('martfury_after_header', 'martfury_page_header');
        remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
        remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
    }
}
