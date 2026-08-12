<?php
/**
 * Front page template.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header();

(new \Maruderm\Homepage\HomepageRenderer())->render();

get_footer();
