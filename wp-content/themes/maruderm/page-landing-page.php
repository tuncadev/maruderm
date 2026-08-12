<?php
/**
 * Template Name: Modern Landing Page
 * Template Post Type: page
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header();

(new \Maruderm\LandingPage\LandingPageRenderer())->render();

get_footer();
