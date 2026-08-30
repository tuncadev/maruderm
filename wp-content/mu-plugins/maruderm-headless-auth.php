<?php
/**
 * Register/login/logout REST endpoints for the headless Next.js frontend.
 *
 * Uses WordPress core Application Passwords (available since 5.6, no extra
 * plugin needed) instead of a JWT plugin: on successful register/login this
 * issues a fresh Application Password for the user, which the Next.js server
 * stores in its own encrypted session cookie and replays as HTTP Basic Auth
 * on subsequent server-to-server requests to WP (REST + GraphQL). This
 * avoids cross-origin cookie problems entirely, since the browser never
 * talks to maruderm.dev directly for authenticated requests.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

const MARUDERM_HEADLESS_APP_PASSWORD_NAME = 'Maruderm Headless Session';

add_action('rest_api_init', 'maruderm_register_auth_routes');

function maruderm_register_auth_routes(): void
{
    register_rest_route('maruderm/v1', '/register', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'maruderm_handle_register',
    ]);

    register_rest_route('maruderm/v1', '/login', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'maruderm_handle_login',
    ]);

    register_rest_route('maruderm/v1', '/logout', [
        'methods' => 'POST',
        'permission_callback' => static fn () => is_user_logged_in(),
        'callback' => 'maruderm_handle_logout',
    ]);
}

function maruderm_handle_register(WP_REST_Request $request): WP_REST_Response
{
    if (! wp_is_application_passwords_available()) {
        return new WP_REST_Response(['error' => 'Application passwords are unavailable on this server (requires HTTPS).'], 500);
    }

    $email = sanitize_email((string) $request->get_param('email'));
    $password = (string) $request->get_param('password');
    $firstName = sanitize_text_field((string) $request->get_param('firstName'));
    $lastName = sanitize_text_field((string) $request->get_param('lastName'));

    if (! is_email($email)) {
        return new WP_REST_Response(['error' => 'Введіть коректний email.'], 400);
    }

    if (strlen($password) < 8) {
        return new WP_REST_Response(['error' => 'Пароль має містити щонайменше 8 символів.'], 400);
    }

    if (email_exists($email)) {
        return new WP_REST_Response(['error' => 'Користувач із таким email вже існує.'], 409);
    }

    $username = maruderm_unique_username_from_email($email);
    $userId = function_exists('wc_create_new_customer')
        ? wc_create_new_customer($email, $username, $password, ['first_name' => $firstName, 'last_name' => $lastName])
        : wp_insert_user(['user_login' => $username, 'user_email' => $email, 'user_pass' => $password]);

    if (is_wp_error($userId)) {
        return new WP_REST_Response(['error' => $userId->get_error_message()], 400);
    }

    if ($firstName !== '' || $lastName !== '') {
        wp_update_user([
            'ID' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim($firstName . ' ' . $lastName) ?: $username,
        ]);
    }

    return maruderm_issue_session_response((int) $userId);
}

function maruderm_handle_login(WP_REST_Request $request): WP_REST_Response
{
    if (! wp_is_application_passwords_available()) {
        return new WP_REST_Response(['error' => 'Application passwords are unavailable on this server (requires HTTPS).'], 500);
    }

    $identifier = (string) $request->get_param('identifier');
    $password = (string) $request->get_param('password');

    $user = wp_authenticate($identifier, $password);

    if (is_wp_error($user)) {
        return new WP_REST_Response(['error' => 'Неправильний email/логін або пароль.'], 401);
    }

    return maruderm_issue_session_response($user->ID);
}

function maruderm_handle_logout(WP_REST_Request $request): WP_REST_Response
{
    $user = wp_get_current_user();
    $uuid = $request->get_param('uuid');

    if ($uuid && class_exists('WP_Application_Passwords')) {
        WP_Application_Passwords::delete_application_password($user->ID, (string) $uuid);
    }

    return new WP_REST_Response(['success' => true]);
}

function maruderm_issue_session_response(int $userId): WP_REST_Response
{
    $user = get_userdata($userId);

    if (! $user instanceof WP_User) {
        return new WP_REST_Response(['error' => 'Користувача не знайдено.'], 404);
    }

    // Each login/register issues its own uniquely-named app password rather
    // than reusing/deleting a fixed-name one, so logging in from a second
    // browser or device doesn't silently invalidate the first session (that
    // first session would keep *looking* logged in locally -- the cookie
    // just holds decrypted session data -- while every authenticated call
    // started failing). Prune old headless passwords beyond a small cap so
    // they don't accumulate indefinitely from repeated logins.
    $existing = WP_Application_Passwords::get_user_application_passwords($userId);
    $headless = array_values(array_filter(
        $existing,
        static fn ($item) => str_starts_with((string) ($item['name'] ?? ''), MARUDERM_HEADLESS_APP_PASSWORD_NAME)
    ));
    usort($headless, static fn ($a, $b) => $a['created'] <=> $b['created']);

    while (count($headless) >= 5) {
        $oldest = array_shift($headless);
        WP_Application_Passwords::delete_application_password($userId, $oldest['uuid']);
    }

    [$newPassword, $item] = WP_Application_Passwords::create_new_application_password($userId, [
        'name' => MARUDERM_HEADLESS_APP_PASSWORD_NAME . ' ' . gmdate('Y-m-d H:i:s'),
    ]);

    return new WP_REST_Response([
        'success' => true,
        'user' => [
            'id' => $userId,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'displayName' => $user->display_name,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
        ],
        'credentials' => [
            'username' => $user->user_login,
            'appPassword' => $newPassword,
            'uuid' => $item['uuid'],
        ],
    ]);
}

function maruderm_unique_username_from_email(string $email): string
{
    $base = sanitize_user(current(explode('@', $email)), true);
    $base = $base !== '' ? $base : 'customer';
    $username = $base;
    $suffix = 1;

    while (username_exists($username)) {
        $suffix++;
        $username = $base . $suffix;
    }

    return $username;
}
