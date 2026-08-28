<?php
/**
 * Canonical public-offer page.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header();
(new \Maruderm\Legal\LegalDocumentRenderer())->render('publicOffer');
get_footer();
