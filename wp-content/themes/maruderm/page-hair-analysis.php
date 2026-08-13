<?php
/**
 * Template Name: Hair Analysis
 *
 * Hair diagnostic route template.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header();
(new \Maruderm\HairAnalysis\HairAnalysisRenderer())->render();
get_footer();
