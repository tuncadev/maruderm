<?php

declare(strict_types=1);

namespace Maruderm\Auth;

use Maruderm\WooCommerce\ProductImageRepository;

if (!defined('ABSPATH')) {
    exit();
}

/** Adapts the canonical login experience to WooCommerce authentication handlers. */
final class LoginRenderer
{
    private ProductImageRepository $images;

    public function __construct(?ProductImageRepository $images = null)
    {
        $this->images = $images ?? new ProductImageRepository();
    }

    public function render(): void
    {
        $mode = $this->requestedMode();
        $accountUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        $heroProducts = $this->heroProducts();

        do_action('woocommerce_before_customer_login_form');
        echo '<main class="login-page"><section class="login-section"><div class="shell login-layout"><div class="login-story">';
        echo '<nav class="breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span><span>Вхід</span></nav>';
        echo '<div class="login-story__copy"><span class="kicker">Maruderm community</span><h1>Твій догляд.<br>Твій ритм.</h1>';
        echo '<p>Повертайся до збережених засобів, стеж за доставкою та відкривай рекомендації, створені саме для тебе.</p></div>';
        echo '<div class="login-story__visual" aria-hidden="true"><span class="login-orbit login-orbit--one"></span><span class="login-orbit login-orbit--two"></span>';

        foreach ($heroProducts as $image) {
            echo '<img src="' . esc_url($image) . '" alt="">';
        }

        echo '<span class="login-story__badge">' . $this->icon('sparkles') . 'Beauty, що пам’ятає тебе</span></div>';
        echo '<div class="login-story__benefits"><span>' . $this->icon('package') . '<strong>Замовлення</strong><small>Усі статуси поруч</small></span>';
        echo '<span>' . $this->icon('heart') . '<strong>Обране</strong><small>Твоя beauty-полиця</small></span>';
        echo '<span>' . $this->icon('sparkles') . '<strong>Бонуси</strong><small>Більше турботи</small></span></div></div>';
        echo '<div class="login-card">';
        $this->renderAuthBlock('page', $mode, $accountUrl);
        echo '</div></div></section></main>';
        do_action('martfury_after_login_form');
        do_action('woocommerce_after_customer_login_form');
    }

    public function renderModal(): void
    {
        $accountUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        $mode = $this->requestedMode();
        $hasAuthError = (isset($_POST['login']) || isset($_POST['register']))
            && function_exists('wc_notice_count')
            && wc_notice_count('error') > 0;
        $modalClasses = $hasAuthError ? 'login-modal is-open' : 'login-modal';

        echo '<div class="login-experience" data-login-experience><div class="' . esc_attr($modalClasses) . '" data-login-modal' . ($hasAuthError ? '' : ' hidden') . '>';
        echo '<button class="login-modal__backdrop" type="button" aria-label="Закрити вікно входу" data-login-close></button>';
        echo '<section class="login-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="login-modal-title">';
        echo '<button class="login-modal__close" type="button" aria-label="Закрити" data-login-close>' . $this->icon('close') . '</button>';
        echo '<div class="login-modal__brand"><a href="' . esc_url(home_url('/')) . '">' . $this->brandLogo() . '</a>';
        echo '<span>nature embraces science</span></div>';
        echo '<div id="login-modal-title" class="sr-only" data-auth-dialog-title data-login-copy="Вхід до особистого кабінету" data-register-copy="Реєстрація особистого кабінету">';
        echo esc_html($mode === 'register' ? 'Реєстрація особистого кабінету' : 'Вхід до особистого кабінету') . '</div>';
        $this->renderAuthBlock('modal', $mode, $accountUrl, $hasAuthError);
        echo '</section></div>';
        $this->renderGoogleOneTap($accountUrl);
        echo '</div>';
    }

    private function requestedMode(): string
    {
        $registrationEnabled = get_option('woocommerce_enable_myaccount_registration') === 'yes';
        $registerRequested = isset($_GET['tab']) && sanitize_key(wp_unslash((string) $_GET['tab'])) === 'register';
        $registerPosted = isset($_POST['register'], $_POST['woocommerce-register-nonce']);

        return $registrationEnabled && ($registerRequested || $registerPosted) ? 'register' : 'login';
    }

    private function renderAuthBlock(string $variant, string $mode, string $accountUrl, bool $hasAuthError = false): void
    {
        $registrationEnabled = get_option('woocommerce_enable_myaccount_registration') === 'yes';
        $isRegistration = $registrationEnabled && $mode === 'register';
        $loginEyebrow = $variant === 'page' ? 'Особистий кабінет' : 'Раді бачити знову';
        $loginHeading = $variant === 'page' ? 'Увійди у свій beauty-простір' : 'Увійти до кабінету';
        $loginDescription = $variant === 'page'
            ? 'Зберігай улюблене, відстежуй замовлення та отримуй персональні рекомендації.'
            : 'Продовжуй покупки, переглядай замовлення та свою персональну добірку.';
        $privacyUrl = get_privacy_policy_url() ?: home_url('/privacy-policy/');

        echo '<div class="login-form-block login-form-block--' . esc_attr($variant) . ($isRegistration ? ' is-registration' : '') . '" data-auth-mode="' . esc_attr($isRegistration ? 'register' : 'login') . '">';

        if ($registrationEnabled) {
            echo '<div class="login-auth-switcher" role="group" aria-label="Вибір способу авторизації">';
            echo '<button class="' . ($isRegistration ? '' : 'is-active') . '" type="button" aria-pressed="' . ($isRegistration ? 'false' : 'true') . '" data-auth-mode="login">Вхід</button>';
            echo '<button class="' . ($isRegistration ? 'is-active' : '') . '" type="button" aria-pressed="' . ($isRegistration ? 'true' : 'false') . '" data-auth-mode="register">Реєстрація</button></div>';
        }

        echo '<div class="login-form-block__heading">';
        echo '<span class="kicker" data-auth-copy data-login-copy="' . esc_attr($loginEyebrow) . '" data-register-copy="Твій beauty-простір">' . esc_html($isRegistration ? 'Твій beauty-простір' : $loginEyebrow) . '</span>';
        echo '<h2 data-auth-copy data-login-copy="' . esc_attr($loginHeading) . '" data-register-copy="Створи свій акаунт">' . esc_html($isRegistration ? 'Створи свій акаунт' : $loginHeading) . '</h2>';
        echo '<p data-auth-copy data-login-copy="' . esc_attr($loginDescription) . '" data-register-copy="Збережи улюблене, бонуси та історію замовлень в одному місці.">';
        echo esc_html($isRegistration ? 'Збережи улюблене, бонуси та історію замовлень в одному місці.' : $loginDescription) . '</p></div>';
        $this->renderSocialProviders($isRegistration ? 'register' : 'login', $accountUrl, $variant === 'modal' ? ['google'] : ['google', 'facebook', 'apple']);
        echo '<div class="login-divider"><span>або за допомогою email</span></div>';
        $this->renderLoginForm($variant, $accountUrl, $hasAuthError && !$isRegistration, $isRegistration);

        if ($registrationEnabled) {
            $this->renderRegisterForm($variant, $accountUrl, $hasAuthError && $isRegistration, !$isRegistration);
            echo '<p class="login-register" data-login-only' . ($isRegistration ? ' hidden' : '') . '>Ще немає акаунта? ';
            echo '<button type="button" data-auth-mode="register">Створити акаунт</button></p>';
            echo '<p class="login-register" data-register-only' . ($isRegistration ? '' : ' hidden') . '>Уже маєш акаунт? ';
            echo '<button type="button" data-auth-mode="login">Увійти</button></p>';
        }

        echo '<p class="login-privacy">Продовжуючи, ви погоджуєтеся з умовами користування та <a href="' . esc_url($privacyUrl) . '">політикою конфіденційності</a>.</p></div>';
    }

    private function renderLoginForm(
        string $variant = 'page',
        string $redirectUrl = '',
        bool $hasLoginError = false,
        bool $hidden = false
    ): void
    {
        $emailId = $variant . '-login-email';
        $passwordId = $variant . '-login-password';

        echo '<form class="woocommerce-form woocommerce-form-login login login-form" method="post" data-login-form data-auth-form="login"' . ($hidden ? ' hidden' : '') . ' novalidate>';
        do_action('woocommerce_login_form_start');
        echo '<label class="login-field" for="' . esc_attr($emailId) . '"><span>Email</span><span class="login-field__control">' . $this->icon('mail');
        echo '<input id="' . esc_attr($emailId) . '" name="username" type="email" autocomplete="username" placeholder="name@email.com" value="' . (!empty($_POST['username']) ? esc_attr(wp_unslash((string) $_POST['username'])) : '') . '" required></span><small data-login-error="email"></small></label>';
        echo '<label class="login-field"><span>Пароль</span><span class="login-field__control">' . $this->icon('lock');
        echo '<input id="' . esc_attr($passwordId) . '" name="password" type="password" autocomplete="current-password" placeholder="Введи пароль" minlength="6" required>';
        echo '<button type="button" aria-label="Показати пароль" data-password-toggle>' . $this->icon('eye') . '</button></span><small data-login-error="password"></small></label>';
        do_action('woocommerce_login_form');
        echo '<div class="login-form__options"><label class="login-remember"><input type="checkbox" name="rememberme" value="forever" checked><span></span>Запам’ятати мене</label>';
        echo '<a href="' . esc_url(wp_lostpassword_url()) . '">Забули пароль?</a></div>';
        wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce');

        if ($redirectUrl !== '') {
            echo '<input type="hidden" name="redirect" value="' . esc_url($redirectUrl) . '">';
        }

        echo '<button class="woocommerce-Button login-submit" type="submit" name="login" value="Увійти до кабінету"><span data-login-submit-label>Увійти до кабінету</span><i aria-hidden="true"></i>' . $this->icon('arrow') . '</button>';
        echo '<p class="login-form__status' . ($hasLoginError ? ' login-form__status--error' : '') . '" data-login-status aria-live="polite">';

        if ($hasLoginError && function_exists('wc_get_notices')) {
            $notices = wc_get_notices('error');
            $firstNotice = $notices[0]['notice'] ?? '';
            echo esc_html(wp_strip_all_tags((string) $firstNotice));
        }

        echo '</p>';
        do_action('woocommerce_login_form_end');
        echo '</form>';
    }

    private function renderRegisterForm(
        string $variant,
        string $redirectUrl,
        bool $hasRegisterError = false,
        bool $hidden = false
    ): void
    {
        $nameId = $variant . '-register-name';
        $emailId = $variant . '-register-email';
        $passwordId = $variant . '-register-password';
        $confirmationId = $variant . '-register-password-confirmation';

        echo '<form class="woocommerce-form woocommerce-form-register register login-form" method="post" data-login-form data-auth-form="register"' . ($hidden ? ' hidden' : '') . ' novalidate>';
        do_action('woocommerce_register_form_start');

        echo '<label class="login-field" for="' . esc_attr($nameId) . '"><span>Ім’я</span><span class="login-field__control">' . $this->icon('user');
        echo '<input id="' . esc_attr($nameId) . '" name="first_name" type="text" autocomplete="name" placeholder="Як до тебе звертатися?" minlength="2" value="' . (!empty($_POST['first_name']) ? esc_attr(wp_unslash((string) $_POST['first_name'])) : '') . '" required></span><small data-login-error="first_name"></small></label>';
        echo '<label class="login-field" for="' . esc_attr($emailId) . '"><span>Email</span><span class="login-field__control">' . $this->icon('mail');
        echo '<input id="' . esc_attr($emailId) . '" name="email" type="email" autocomplete="email" placeholder="name@email.com" value="' . (!empty($_POST['email']) ? esc_attr(wp_unslash((string) $_POST['email'])) : '') . '" required></span><small data-login-error="email"></small></label>';
        echo '<label class="login-field" for="' . esc_attr($passwordId) . '"><span>Пароль</span><span class="login-field__control">' . $this->icon('lock');
        echo '<input id="' . esc_attr($passwordId) . '" name="password" type="password" autocomplete="new-password" placeholder="Створи пароль" minlength="6" required>';
        echo '<button type="button" aria-label="Показати пароль" data-password-toggle>' . $this->icon('eye') . '</button></span><small data-login-error="password"></small></label>';
        echo '<label class="login-field" for="' . esc_attr($confirmationId) . '"><span>Повтори пароль</span><span class="login-field__control">' . $this->icon('lock');
        echo '<input id="' . esc_attr($confirmationId) . '" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Повтори пароль" minlength="6" required>';
        echo '<button type="button" aria-label="Показати пароль" data-password-toggle>' . $this->icon('eye') . '</button></span><small data-login-error="password_confirmation"></small></label>';

        do_action('woocommerce_register_form');
        wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce');
        echo '<input type="hidden" name="maruderm_registration" value="1">';

        if ($redirectUrl !== '') {
            echo '<input type="hidden" name="redirect" value="' . esc_url($redirectUrl) . '">';
        }

        echo '<button class="woocommerce-Button login-submit" type="submit" name="register" value="Створити акаунт"><span data-login-submit-label>Створити акаунт</span><i aria-hidden="true"></i>' . $this->icon('arrow') . '</button>';
        echo '<p class="login-form__status' . ($hasRegisterError ? ' login-form__status--error' : '') . '" data-login-status aria-live="polite">';

        if ($hasRegisterError && function_exists('wc_get_notices')) {
            $notices = wc_get_notices('error');
            $firstNotice = $notices[0]['notice'] ?? '';
            echo esc_html(wp_strip_all_tags((string) $firstNotice));
        }

        echo '</p>';
        do_action('woocommerce_register_form_end');
        echo '</form>';
    }

    /** @param string[] $providers */
    private function renderSocialProviders(string $mode, string $accountUrl, array $providers = ['google', 'facebook', 'apple']): void
    {
        $labels = ['google' => 'Google', 'facebook' => 'Facebook', 'apple' => 'Apple'];

        foreach ($providers as $provider) {
            if (!isset($labels[$provider])) {
                continue;
            }

            $label = $labels[$provider];
            $loginUrl = $this->socialAuthUrl($provider, 'login', $accountUrl);
            $registerUrl = $this->socialAuthUrl($provider, 'register', $accountUrl);
            $url = $mode === 'register' ? $registerUrl : $loginUrl;
            echo '<a class="google-login-button" href="' . esc_url($url) . '" data-social-provider="' . esc_attr($provider) . '" data-auth-login-url="' . esc_url($loginUrl) . '" data-auth-register-url="' . esc_url($registerUrl) . '">';
            echo $this->providerIcon($provider) . '<span>Продовжити з ' . esc_html($label) . '</span><i>' . $this->icon('arrow') . '</i></a>';
        }
    }

    private function renderGoogleOneTap(string $accountUrl): void
    {
        $googleUrl = $this->socialAuthUrl('google', 'login', $accountUrl);

        echo '<aside class="google-one-tap" data-google-one-tap hidden aria-label="Швидкий вхід через Google">';
        echo '<div class="google-one-tap__head"><span class="google-wordmark"><b>G</b> Sign in with Google</span>';
        echo '<button type="button" aria-label="Закрити підказку Google" data-one-tap-close>×</button></div>';
        echo '<div class="google-one-tap__body"><span class="google-one-tap__avatar">G</span><div>';
        echo '<strong>Continue with Google</strong><small>Безпечний вхід до Maruderm</small></div>' . $this->icon('arrow') . '</div>';
        echo '<a class="google-one-tap__continue" href="' . esc_url($googleUrl) . '">Continue</a>';
        echo '<small class="google-one-tap__privacy">Google підтвердить ваші дані перед входом.</small></aside>';
    }

    private function socialAuthUrl(string $provider, string $mode, string $accountUrl): string
    {
        return add_query_arg([
            'action' => 'social_auth_start',
            'provider' => $provider,
            'mode' => $mode,
            'popup' => '1',
            'redirect_to' => add_query_arg('tab', $mode, $accountUrl),
            'success_redirect' => $accountUrl,
        ], admin_url('admin-post.php'));
    }

    /** @return string[] */
    private function heroProducts(): array
    {
        $images = [];

        foreach ([6062, 6007] as $productId) {
            $product = function_exists('wc_get_product') ? wc_get_product($productId) : false;
            $images[] = $product instanceof \WC_Product
                ? $this->images->primaryUrl($product)
                : (function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : '');
        }

        return $images;
    }

    private function providerIcon(string $provider): string
    {
        if ($provider === 'google') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.9h5.4a4.6 4.6 0 0 1-2 3v2.5h3.3c1.9-1.8 2.9-4.4 2.9-7.4Z"></path><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.3-2.5c-.9.6-2.1 1-3.4 1a5.9 5.9 0 0 1-5.5-4.1H3.1v2.6A10 10 0 0 0 12 22Z"></path><path fill="#FBBC05" d="M6.5 14a6 6 0 0 1 0-3.8V7.6H3.1a10 10 0 0 0 0 9l3.4-2.6Z"></path><path fill="#EA4335" d="M12 6.1c1.5 0 2.9.5 4 1.6l3-3A10 10 0 0 0 3.1 7.6l3.4 2.6A5.9 5.9 0 0 1 12 6.1Z"></path></svg>';
        }

        $letter = $provider === 'facebook' ? 'f' : '●';

        return '<svg viewBox="0 0 24 24" aria-hidden="true"><text x="12" y="17" text-anchor="middle" font-size="16" font-weight="700">' . esc_html($letter) . '</text></svg>';
    }

    private function icon(string $name): string
    {
        $paths = [
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"></path>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path>',
            'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>',
            'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle>',
            'user' => '<circle cx="12" cy="7" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>',
            'heart' => '<path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"></path>',
            'package' => '<path d="m4 7 8-4 8 4-8 4-8-4Z"></path><path d="M4 7v10l8 4 8-4V7M12 11v10"></path>',
            'sparkles' => '<path d="m12 3 1.5 4.2L18 9l-4.5 1.8L12 15l-1.5-4.2L6 9l4.5-1.8L12 3Z"></path>',
            'close' => '<path d="M6 6l12 12M18 6 6 18"></path>',
        ];

        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
    }

    private function brandLogo(): string
    {
        $logoUrl = function_exists('martfury_get_option') ? (string) martfury_get_option('logo') : '';

        if ($logoUrl === '') {
            $logoUrl = get_template_directory_uri() . '/images/logo/logo.png';
        }

        return '<img src="' . esc_url($logoUrl) . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
    }
}
