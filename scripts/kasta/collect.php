<?php
/** CLI snapshot adapter for the Kasta MU-plugin collector. */
if (! defined('ABSPATH') || ! defined('WP_CLI') || ! WP_CLI) { exit(1); }
require_once ABSPATH . 'wp-content/mu-plugins/maruderm-kasta-feed/class-source.php';
require_once ABSPATH . 'wp-content/mu-plugins/maruderm-kasta-feed/class-source-collector.php';
echo wp_json_encode((new MarudermKastaSourceCollector())->collect(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
