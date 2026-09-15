<?php
/**
 * Plugin Name: Maruderm Kasta XML Feed
 * Description: Controlled Kasta XML generation and private file delivery with WooCommerce settings.
 * Version: 1.0.0
 */
if (! defined('ABSPATH')) { exit; }

foreach (['settings', 'storage', 'endpoint', 'source', 'source-collector', 'catalog', 'media', 'generator', 'admin'] as $marudermKastaModule) {
    require_once __DIR__ . '/maruderm-kasta-feed/class-' . $marudermKastaModule . '.php';
}
unset($marudermKastaModule);

$marudermKastaSettings = new \Maruderm\Kasta\Settings();
$marudermKastaStorage = new \Maruderm\Kasta\Storage();
$marudermKastaGenerator = new \Maruderm\Kasta\Generator($marudermKastaSettings, $marudermKastaStorage, new \MarudermKastaSourceCollector());
add_filter('cron_schedules', [$marudermKastaSettings, 'intervals']);
add_action(\Maruderm\Kasta\Settings::MANUAL_HOOK, [$marudermKastaGenerator, 'run']);
add_action(\Maruderm\Kasta\Settings::AUTO_HOOK, [$marudermKastaGenerator, 'automatic']);
// Run before the existing headless access gate, without altering login/admin routes.
add_action('init', [new \Maruderm\Kasta\Endpoint($marudermKastaSettings, $marudermKastaStorage), 'serve'], -1100);
(new \Maruderm\Kasta\Admin($marudermKastaSettings, $marudermKastaGenerator))->register();
if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('kasta-feed generate', [$marudermKastaGenerator, 'cli']);
}
unset($marudermKastaSettings, $marudermKastaStorage, $marudermKastaGenerator);
