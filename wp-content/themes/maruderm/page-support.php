<?php
/**
 * Canonical support information page.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header();

$support_slug = \Maruderm\Support\SupportPage::currentSlug();

if ($support_slug !== null) {
    (new \Maruderm\Support\SupportPageRenderer())->render($support_slug);
}

get_footer();
