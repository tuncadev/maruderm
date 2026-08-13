<?php
/**
 * Login page template.
 *
 * @package Maruderm
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    wp_safe_redirect(wc_get_page_permalink('myaccount'));
    exit;
}

get_header();
(new \Maruderm\Auth\LoginRenderer())->render();
get_footer();
