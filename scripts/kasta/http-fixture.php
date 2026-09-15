<?php
/** Only used by test-http.py with an isolated temporary document root. */
if (! getenv('KASTA_TEST_ROOT')) { http_response_code(404); exit; }
require __DIR__ . '/wp-test-stubs.php';
define('ABSPATH', getenv('KASTA_TEST_ROOT') . '/public/');
$_SERVER['DOCUMENT_ROOT'] = ABSPATH;
require dirname(__DIR__, 2) . '/wp-content/mu-plugins/maruderm-kasta-feed.php';
$GLOBALS['kasta_options'] = json_decode(file_get_contents(getenv('KASTA_TEST_ROOT') . '/options.json'), true);
$endpoint = new \Maruderm\Kasta\Endpoint(new \Maruderm\Kasta\Settings(), new \Maruderm\Kasta\Storage(getenv('KASTA_TEST_ROOT') . '/private'));
$endpoint->serve();
return false;
