<?php
/**
 * Canonical sales-terms and privacy page.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header();
(new \Maruderm\Legal\LegalDocumentRenderer())->render('termsAndPrivacy');
get_footer();
