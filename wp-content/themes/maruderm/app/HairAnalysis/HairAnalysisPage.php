<?php

declare(strict_types=1);

namespace Maruderm\HairAnalysis;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns page-level integration for the Hair Analysis page template. */
final class HairAnalysisPage implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_action('wp', [$this, 'removeInheritedLayout'], 20);
    }

    public static function isCurrent(): bool
    {
        return is_page_template('page-hair-analysis.php') || is_page('hair-analysis');
    }

    public function removeInheritedLayout(): void
    {
        if (!self::isCurrent()) {
            return;
        }

        remove_action('martfury_after_header', 'martfury_page_header');
        remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
        remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
    }
}
