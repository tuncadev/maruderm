<?php
/** Isolated WordPress API doubles for Kasta regression tests; never loaded by the plugin. */
$GLOBALS['kasta_options'] = $GLOBALS['kasta_events'] = $GLOBALS['kasta_actions'] = [];
$GLOBALS['kasta_allowed'] = true;
$GLOBALS['kasta_nonce_valid'] = true;
$GLOBALS['kasta_media_ok'] = true;
function home_url($path = '', $scheme = null) { return 'https://wp.maruderm.com.ua' . $path; }
function get_option($key, $default = false) { return $GLOBALS['kasta_options'][$key] ?? $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['kasta_options'][$key] = $value; return true; }
function wp_clear_scheduled_hook($hook) { unset($GLOBALS['kasta_events'][$hook]); return 1; }
function wp_next_scheduled($hook) { return isset($GLOBALS['kasta_events'][$hook]) ? $GLOBALS['kasta_events'][$hook]['time'] : false; }
function wp_schedule_event($time, $interval, $hook, $args = [], $wpError = false) { $GLOBALS['kasta_events'][$hook] = compact('time', 'interval'); return true; }
function wp_schedule_single_event($time, $hook, $args = [], $wpError = false) { return wp_schedule_event($time, '', $hook); }
function spawn_cron() { return true; }
function is_wp_error($value) { return false; }
function wp_raise_memory_limit($context) { return true; }
function wp_parse_url($url) { return parse_url($url); }
function wp_safe_remote_get($url, $args) {
    return ['status' => $GLOBALS['kasta_media_ok'] ? 200 : 429, 'body' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')];
}
function wp_remote_retrieve_response_code($response) { return $response['status']; }
function wp_remote_retrieve_header($response, $header) { return 'image/png'; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function current_user_can($capability) { return $GLOBALS['kasta_allowed'] && $capability === 'manage_woocommerce'; }
function wp_die($message, $title = '', $args = []) { throw new RuntimeException('wp_die:' . ($args['response'] ?? 500)); }
function check_admin_referer($action) { if (! $GLOBALS['kasta_nonce_valid']) { throw new RuntimeException('invalid nonce'); } return true; }
function wp_unslash($value) { return $value; }
function wp_safe_redirect($url) { throw new RuntimeException('redirect'); }
function admin_url($path = '') { return 'https://wp.maruderm.com.ua/wp-admin/' . $path; }
function get_current_user_id() { return 1; }
function set_transient($key, $value, $ttl) { $GLOBALS['kasta_options'][$key] = $value; }
function get_transient($key) { return $GLOBALS['kasta_options'][$key] ?? false; }
function delete_transient($key) { unset($GLOBALS['kasta_options'][$key]); }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return esc_html($text); }
function esc_url($text) { return esc_html($text); }
function checked($value) { echo $value ? 'checked' : ''; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="fixture">'; }
function submit_button($label, $class = '') { echo '<button>' . esc_html($label) . '</button>'; }
function wp_date($format, $timestamp) { return gmdate($format, $timestamp); }
function add_action($hook, $callback, $priority = 10) { $GLOBALS['kasta_actions'][$hook][$priority][] = $callback; }
function add_filter($hook, $callback, $priority = 10) { add_action($hook, $callback, $priority); }
function add_submenu_page(...$args) { $GLOBALS['kasta_submenu'] = $args; }
function nocache_headers() { header('Cache-Control: no-store'); }
function status_header($status) { http_response_code($status); }
