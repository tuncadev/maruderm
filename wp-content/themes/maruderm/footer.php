<?php
/**
 * Canonical storefront footer and document close.
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

do_action('martfury_before_site_content_close');
?>
</div><!-- #content -->
<?php
do_action('martfury_before_footer');

if (!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('footer')) {
    (new \Maruderm\Layout\FooterRenderer())->render();
    do_action('martfury_after_footer');
}

(new \Maruderm\Campaign\CampaignPopupRenderer())->render();
?>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
