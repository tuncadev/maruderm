<?php
/**
 * Template Name: Login/Register
 *
 * @package Maruderm
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$activeTab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'login';
$activeTab = in_array($activeTab, ['login', 'register'], true) ? $activeTab : 'login';

$errors = [];
$messages = [];

$socialErrorMap = [
    'social_login_failed' => __('Не вдалося виконати вхід через соцмережу. Використайте той самий соціальний акаунт, який уже прив’язаний.', 'maruderm'),
    'social_register_failed' => __('Не вдалося виконати реєстрацію через соцмережу. Цей email може вже використовуватися іншим акаунтом.', 'maruderm'),
    'invalid_state' => __('Стан безпеки недійсний або прострочений. Спробуйте ще раз.', 'maruderm'),
    'invalid_callback' => __('Отримано некоректну відповідь від соціального провайдера.', 'maruderm'),
    'missing_provider' => __('Не вказано соціальний провайдер.', 'maruderm'),
    'start_failed' => __('Не вдалося розпочати соціальну авторизацію.', 'maruderm'),
];

if (isset($_GET['social_auth_error'])) {
    $errorCode = sanitize_key((string) $_GET['social_auth_error']);
    $errors[] = $socialErrorMap[$errorCode] ?? __('Соціальна авторизація не вдалася.', 'maruderm');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maruderm_auth_action'])) {
    $postedAction = sanitize_key((string) wp_unslash($_POST['maruderm_auth_action']));

    if (! in_array($postedAction, ['login', 'register'], true)) {
        $errors[] = __('Некоректна дія форми.', 'maruderm');
    } elseif (! isset($_POST['maruderm_auth_nonce']) || ! wp_verify_nonce((string) wp_unslash($_POST['maruderm_auth_nonce']), 'maruderm_auth_' . $postedAction)) {
        $errors[] = __('Перевірка безпеки не пройдена. Спробуйте ще раз.', 'maruderm');
    } elseif ($postedAction === 'login') {
        $activeTab = 'login';
        $identifier = sanitize_text_field((string) wp_unslash($_POST['auth_identifier'] ?? ''));
        $password = (string) wp_unslash($_POST['auth_password'] ?? '');
        $remember = isset($_POST['auth_remember']) && $_POST['auth_remember'] === '1';

        if ($identifier === '' || $password === '') {
            $errors[] = __('Введіть email/імʼя користувача та пароль.', 'maruderm');
        } else {
            $userLogin = $identifier;
            if (is_email($identifier)) {
                $user = get_user_by('email', $identifier);
                if (! $user) {
                    $errors[] = __('Користувача з таким email не знайдено.', 'maruderm');
                } else {
                    $userLogin = (string) $user->user_login;
                }
            }

            if ($errors === []) {
                $user = wp_signon([
                    'user_login' => $userLogin,
                    'user_password' => $password,
                    'remember' => $remember,
                ], is_ssl());

                if (is_wp_error($user)) {
                    $errors[] = __('Невірні облікові дані. Спробуйте ще раз.', 'maruderm');
                } else {
                    wp_safe_redirect(home_url('/my-account/'));
                    exit;
                }
            }
        }
    } else {
        $activeTab = 'register';
        $username = sanitize_user((string) wp_unslash($_POST['reg_username'] ?? ''), true);
        $email = sanitize_email((string) wp_unslash($_POST['reg_email'] ?? ''));
        $password = (string) wp_unslash($_POST['reg_password'] ?? '');
        $passwordConfirm = (string) wp_unslash($_POST['reg_password_confirm'] ?? '');

        if ($email === '' || ! is_email($email)) {
            $errors[] = __('Введіть коректну email-адресу.', 'maruderm');
        }
        if ($password === '' || strlen($password) < 8) {
            $errors[] = __('Пароль має містити щонайменше 8 символів.', 'maruderm');
        }
        if ($password !== $passwordConfirm) {
            $errors[] = __('Підтвердження пароля не збігається.', 'maruderm');
        }
        if ($email !== '' && email_exists($email)) {
            $errors[] = __('Акаунт з таким email вже існує.', 'maruderm');
        }

        if ($username === '' && $email !== '') {
            $emailParts = explode('@', $email);
            $base = sanitize_user((string) ($emailParts[0] ?? 'user'), true);
            $base = $base !== '' ? $base : 'user';
            $candidate = $base;
            $suffix = 1;
            while (username_exists($candidate)) {
                $candidate = $base . $suffix;
                $suffix++;
            }
            $username = $candidate;
        }

        if ($username === '' || username_exists($username)) {
            $errors[] = __('Імʼя користувача некоректне або вже зайняте.', 'maruderm');
        }

        if ($errors === []) {
            $userId = wp_create_user($username, $password, $email);
            if (is_wp_error($userId)) {
                $errors[] = __('Наразі не вдалося створити акаунт.', 'maruderm');
            } else {
                wp_update_user(['ID' => (int) $userId, 'display_name' => $username]);
                wp_set_current_user((int) $userId);
                wp_set_auth_cookie((int) $userId, true);
                do_action('wp_login', $username, get_user_by('id', (int) $userId));
                wp_safe_redirect(home_url('/my-account/'));
                exit;
            }
        }
    }
}

$currentUrl = get_permalink();
if (! is_string($currentUrl) || $currentUrl === '') {
    $currentUrl = home_url('/login/');
}

$socialProviders = [
    'google' => [
        'label' => 'Google',
        'tagline' => __('Швидкий вхід до кабінету', 'maruderm'),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.22-2.34H12v4.43h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.72Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.9l-3.88-3A7.21 7.21 0 0 1 12 19.3a7.15 7.15 0 0 1-6.76-4.93H1.24v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.24 14.37A7.25 7.25 0 0 1 4.84 12c0-.82.14-1.62.4-2.37V6.53H1.24A12 12 0 0 0 0 12c0 1.93.46 3.75 1.24 5.47l4-3.1Z"/><path fill="#EA4335" d="M12 4.77c1.77 0 3.36.61 4.61 1.82l3.46-3.46C17.95 1.15 15.23 0 12 0A12 12 0 0 0 1.24 6.53l4 3.1A7.15 7.15 0 0 1 12 4.77Z"/></svg>',
    ],
    'facebook' => [
        'label' => 'Facebook',
        'tagline' => __('Вхід в один дотик', 'maruderm'),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.5h3.05V9.4c0-3.03 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.98h-1.51c-1.49 0-1.95.93-1.95 1.87v2.25h3.32l-.53 3.5h-2.79V24C19.61 23.1 24 18.1 24 12.07Z"/></svg>',
    ],
    'apple' => [
        'label' => 'Apple',
        'tagline' => __('Підтримка приватного email', 'maruderm'),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#111111" d="M16.37 12.27c.03 3.02 2.65 4.03 2.68 4.04-.02.07-.42 1.43-1.38 2.84-.83 1.22-1.7 2.43-3.06 2.45-1.34.02-1.77-.8-3.3-.8-1.53 0-2.01.78-3.27.82-1.3.05-2.28-1.3-3.12-2.52C3.18 16.47 2 11.7 3.8 8.56c.9-1.55 2.52-2.53 4.27-2.56 1.33-.03 2.59.9 3.3.9.7 0 2.13-1.1 3.59-.93.61.03 2.33.25 3.43 1.86-.09.06-2.04 1.19-2.02 3.44Zm-2.33-7.8c.69-.83 1.15-1.98 1.03-3.13-1 .04-2.2.66-2.92 1.5-.64.74-1.2 1.92-1.05 3.05 1.11.09 2.24-.57 2.94-1.42Z"/></svg>',
    ],
];

$buildSocialUrl = static function (string $provider, string $mode, string $redirectTo, string $successRedirect): string {
    return add_query_arg([
        'action' => 'social_auth_start',
        'provider' => $provider,
        'mode' => $mode,
        'popup' => '1',
        'redirect_to' => $redirectTo,
        'success_redirect' => $successRedirect,
    ], admin_url('admin-post.php'));
};

get_header();
?>
<section class="md-auth-page" data-initial-tab="<?php echo esc_attr($activeTab); ?>">
    <div class="md-auth-shell">
        <div class="md-auth-brand">
            <p class="md-auth-kicker"><?php echo esc_html__('Кабінет покупця', 'maruderm'); ?></p>
            <h1><?php echo esc_html__('Ласкаво просимо до косметичного маркетплейсу Maruderm', 'maruderm'); ?></h1>
            <p class="md-auth-subtext"><?php echo esc_html__('Увійдіть або зареєструйтеся, щоб керувати замовленнями, обраним та персональними пропозиціями.', 'maruderm'); ?></p>
        </div>

        <div class="md-auth-card">
            <div class="md-auth-tabs" role="tablist" aria-label="<?php echo esc_attr__('Вкладки авторизації', 'maruderm'); ?>">
                <button class="md-tab-btn" data-tab="login" role="tab" aria-controls="md-tab-login" type="button"><?php echo esc_html__('Вхід', 'maruderm'); ?></button>
                <button class="md-tab-btn" data-tab="register" role="tab" aria-controls="md-tab-register" type="button"><?php echo esc_html__('Реєстрація', 'maruderm'); ?></button>
            </div>

            <?php if ($errors !== []): ?>
                <div class="md-auth-alert md-auth-alert-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo esc_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($messages !== []): ?>
                <div class="md-auth-alert md-auth-alert-info" role="status">
                    <?php foreach ($messages as $message): ?>
                        <p><?php echo esc_html($message); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="md-auth-tab" id="md-tab-login" data-tab-panel="login" role="tabpanel">
                <form method="post" class="md-auth-form" novalidate>
                    <input type="hidden" name="maruderm_auth_action" value="login" />
                    <?php wp_nonce_field('maruderm_auth_login', 'maruderm_auth_nonce'); ?>

                    <label class="md-field"><span><?php echo esc_html__('Email або імʼя користувача', 'maruderm'); ?></span><input type="text" name="auth_identifier" autocomplete="username" required /></label>
                    <label class="md-field"><span><?php echo esc_html__('Пароль', 'maruderm'); ?></span><input type="password" name="auth_password" autocomplete="current-password" required /></label>

                    <label class="md-check"><input type="checkbox" name="auth_remember" value="1" /><span><?php echo esc_html__('Запам’ятати мене', 'maruderm'); ?></span></label>

                    <button class="md-primary-btn" type="submit"><?php echo esc_html__('Увійти', 'maruderm'); ?></button>
                </form>

                <div class="md-social-grid">
                    <?php foreach ($socialProviders as $provider => $meta): ?>
                        <a class="md-social-btn md-social-<?php echo esc_attr($provider); ?>" href="<?php echo esc_url($buildSocialUrl($provider, 'login', add_query_arg('tab', 'login', $currentUrl), home_url('/'))); ?>">
                            <span class="md-social-icon"><?php echo $meta['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="md-social-copy"><strong><?php echo esc_html(sprintf(__('Увійти через %s', 'maruderm'), $meta['label'])); ?></strong><small><?php echo esc_html($meta['tagline']); ?></small></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="md-auth-tab" id="md-tab-register" data-tab-panel="register" role="tabpanel">
                <form method="post" class="md-auth-form" novalidate>
                    <input type="hidden" name="maruderm_auth_action" value="register" />
                    <?php wp_nonce_field('maruderm_auth_register', 'maruderm_auth_nonce'); ?>

                    <label class="md-field"><span><?php echo esc_html__('Імʼя користувача (необов’язково)', 'maruderm'); ?></span><input type="text" name="reg_username" autocomplete="username" /></label>
                    <label class="md-field"><span><?php echo esc_html__('Email', 'maruderm'); ?></span><input type="email" name="reg_email" autocomplete="email" required /></label>
                    <label class="md-field"><span><?php echo esc_html__('Пароль', 'maruderm'); ?></span><input type="password" name="reg_password" autocomplete="new-password" required /></label>
                    <label class="md-field"><span><?php echo esc_html__('Підтвердіть пароль', 'maruderm'); ?></span><input type="password" name="reg_password_confirm" autocomplete="new-password" required /></label>

                    <button class="md-primary-btn" type="submit"><?php echo esc_html__('Створити акаунт', 'maruderm'); ?></button>
                </form>

                <div class="md-social-grid">
                    <?php foreach ($socialProviders as $provider => $meta): ?>
                        <a class="md-social-btn md-social-<?php echo esc_attr($provider); ?>" href="<?php echo esc_url($buildSocialUrl($provider, 'register', add_query_arg('tab', 'register', $currentUrl), home_url('/'))); ?>">
                            <span class="md-social-icon"><?php echo $meta['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="md-social-copy"><strong><?php echo esc_html(sprintf(__('Зареєструватися через %s', 'maruderm'), $meta['label'])); ?></strong><small><?php echo esc_html($meta['tagline']); ?></small></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
get_footer();
