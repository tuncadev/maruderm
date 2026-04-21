<?php
/**
 * My Account login/register modernized template override.
 *
 * @package Maruderm
 * @version 9.9.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$register_enabled = get_option('woocommerce_enable_myaccount_registration') === 'yes';
$active_tab = 'login';

if (! empty($_POST) && isset($_POST['woocommerce-register-nonce']) && ! empty($_POST['woocommerce-register-nonce'])) {
    $active_tab = 'register';
}

if (isset($_GET['tab'])) {
    $requested_tab = sanitize_key((string) $_GET['tab']);
    if (in_array($requested_tab, ['login', 'register'], true)) {
        $active_tab = $requested_tab;
    }
}

if (! $register_enabled && $active_tab === 'register') {
    $active_tab = 'login';
}

$social_error_map = [
    'social_login_failed' => __('Не вдалося виконати вхід через соцмережу. Використайте той самий соціальний акаунт, який уже прив’язаний.', 'maruderm'),
    'social_register_failed' => __('Не вдалося виконати реєстрацію через соцмережу. Цей email може вже використовуватися іншим акаунтом.', 'maruderm'),
    'invalid_state' => __('Стан безпеки недійсний або прострочений. Спробуйте ще раз.', 'maruderm'),
    'invalid_callback' => __('Отримано некоректну відповідь від соціального провайдера.', 'maruderm'),
    'start_failed' => __('Не вдалося розпочати соціальну авторизацію.', 'maruderm'),
    'missing_provider' => __('Не вказано соціальний провайдер.', 'maruderm'),
];

$social_error = '';
if (isset($_GET['social_auth_error'])) {
    $error_code = sanitize_key((string) $_GET['social_auth_error']);
    $social_error = $social_error_map[$error_code] ?? __('Соціальна авторизація не вдалася.', 'maruderm');
}

$myaccount_url = wc_get_page_permalink('myaccount');
if (! is_string($myaccount_url) || $myaccount_url === '') {
    $myaccount_url = home_url('/my-account/');
}

$social_providers = [
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

$build_social_url = static function (string $provider, string $mode, string $redirect_to, string $success_redirect): string {
    return add_query_arg([
        'action' => 'social_auth_start',
        'provider' => $provider,
        'mode' => $mode,
        'popup' => '1',
        'redirect_to' => $redirect_to,
        'success_redirect' => $success_redirect,
    ], admin_url('admin-post.php'));
};

do_action('woocommerce_before_customer_login_form');
?>
<div class="md-wc-auth" data-initial-tab="<?php echo esc_attr($active_tab); ?>">
    <div class="md-wc-auth__inner">
        <div class="md-wc-auth__card">
            <div class="md-wc-auth__tabs" role="tablist" aria-label="<?php esc_attr_e('Вкладки акаунта', 'maruderm'); ?>">
                <button class="md-wc-auth__tab-btn" type="button" data-tab="login" role="tab" aria-controls="md-wc-login"><?php esc_html_e('Вхід', 'maruderm'); ?></button>
                <?php if ($register_enabled) : ?>
                    <button class="md-wc-auth__tab-btn" type="button" data-tab="register" role="tab" aria-controls="md-wc-register"><?php esc_html_e('Реєстрація', 'maruderm'); ?></button>
                <?php endif; ?>
            </div>

            <?php if ($social_error !== '') : ?>
                <div class="md-wc-auth__alert md-wc-auth__alert--error"><?php echo esc_html($social_error); ?></div>
            <?php endif; ?>

            <div class="md-wc-auth__panel" id="md-wc-login" data-tab-panel="login" role="tabpanel">
                <form class="woocommerce-form woocommerce-form-login login md-wc-auth__form" method="post">
                    <?php do_action('woocommerce_login_form_start'); ?>

                    <label class="md-wc-auth__field"><span><?php esc_html_e('Email або імʼя користувача', 'maruderm'); ?></span><input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" autocomplete="username" value="<?php echo ! empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required /></label>
                    <label class="md-wc-auth__field"><span><?php esc_html_e('Пароль', 'maruderm'); ?></span><input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" autocomplete="current-password" required /></label>

                    <?php do_action('woocommerce_login_form'); ?>

                    <div class="md-wc-auth__meta">
                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme md-wc-auth__remember"><input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" value="forever" /><span><?php esc_html_e('Запам’ятати мене', 'maruderm'); ?></span></label>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Забули пароль?', 'maruderm'); ?></a>
                    </div>

                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="woocommerce-Button button md-wc-auth__submit" name="login" value="<?php esc_attr_e('Увійти', 'maruderm'); ?>"><?php esc_html_e('Увійти', 'maruderm'); ?></button>

                    <?php do_action('woocommerce_login_form_end'); ?>
                </form>

                <div class="md-wc-auth__social-grid">
                    <?php foreach ($social_providers as $provider => $meta) : ?>
                        <a class="md-wc-auth__social md-wc-auth__social--<?php echo esc_attr($provider); ?>" href="<?php echo esc_url($build_social_url($provider, 'login', add_query_arg('tab', 'login', $myaccount_url), home_url('/'))); ?>">
                            <span class="md-wc-auth__icon"><?php echo $meta['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="md-wc-auth__copy"><strong><?php echo esc_html(sprintf(__('Увійти через %s', 'maruderm'), $meta['label'])); ?></strong><small><?php echo esc_html($meta['tagline']); ?></small></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($register_enabled) : ?>
                <div class="md-wc-auth__panel" id="md-wc-register" data-tab-panel="register" role="tabpanel">
                    <form method="post" class="woocommerce-form woocommerce-form-register register md-wc-auth__form" <?php do_action('woocommerce_register_form_tag'); ?>>
                        <?php do_action('woocommerce_register_form_start'); ?>

                        <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                            <label class="md-wc-auth__field"><span><?php esc_html_e('Імʼя користувача', 'maruderm'); ?></span><input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" autocomplete="username" value="<?php echo ! empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required /></label>
                        <?php endif; ?>

                        <label class="md-wc-auth__field"><span><?php esc_html_e('Email', 'maruderm'); ?></span><input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" autocomplete="email" value="<?php echo ! empty($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" required /></label>

                        <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
                            <label class="md-wc-auth__field"><span><?php esc_html_e('Пароль', 'maruderm'); ?></span><input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" autocomplete="new-password" required /></label>
                        <?php else : ?>
                            <p class="md-wc-auth__hint"><?php esc_html_e('Пароль буде надіслано на вашу email-адресу.', 'maruderm'); ?></p>
                        <?php endif; ?>

                        <?php do_action('woocommerce_register_form'); ?>

                        <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                        <button type="submit" class="woocommerce-Button button md-wc-auth__submit" name="register" value="<?php esc_attr_e('Зареєструватися', 'maruderm'); ?>"><?php esc_html_e('Зареєструватися', 'maruderm'); ?></button>

                        <?php do_action('woocommerce_register_form_end'); ?>
                    </form>

                    <div class="md-wc-auth__social-grid">
                        <?php foreach ($social_providers as $provider => $meta) : ?>
                            <a class="md-wc-auth__social md-wc-auth__social--<?php echo esc_attr($provider); ?>" href="<?php echo esc_url($build_social_url($provider, 'register', add_query_arg('tab', 'register', $myaccount_url), home_url('/'))); ?>">
                                <span class="md-wc-auth__icon"><?php echo $meta['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                <span class="md-wc-auth__copy"><strong><?php echo esc_html(sprintf(__('Зареєструватися через %s', 'maruderm'), $meta['label'])); ?></strong><small><?php echo esc_html($meta['tagline']); ?></small></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php do_action('martfury_after_login_form'); ?>
<?php do_action('woocommerce_after_customer_login_form'); ?>
