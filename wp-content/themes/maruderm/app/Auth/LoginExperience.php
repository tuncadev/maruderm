<?php

declare(strict_types=1);

namespace Maruderm\Auth;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns the global logged-out login drawer rendered directly after the header. */
final class LoginExperience implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_action('martfury_after_header', [$this, 'render'], 1);
        add_filter('pre_option_woocommerce_registration_generate_password', [$this, 'useCustomerChosenPassword']);
        add_filter('woocommerce_process_registration_errors', [$this, 'validateRegistration'], 10, 4);
        add_filter('woocommerce_new_customer_data', [$this, 'addCustomerName']);
    }

    public function render(): void
    {
        if (is_user_logged_in() || is_page_template('template-coming-soon-page.php')) {
            return;
        }

        (new LoginRenderer())->renderModal();
    }

    public function useCustomerChosenPassword(mixed $preOption): mixed
    {
        if (is_admin() || get_option('woocommerce_enable_myaccount_registration') !== 'yes') {
            return $preOption;
        }

        return 'no';
    }

    public function validateRegistration(\WP_Error $errors, string $username, string $password, string $email): \WP_Error
    {
        if (!isset($_POST['maruderm_registration'])) {
            return $errors;
        }

        $firstName = isset($_POST['first_name'])
            ? sanitize_text_field(wp_unslash((string) $_POST['first_name']))
            : '';
        $confirmation = isset($_POST['password_confirmation'])
            ? (string) wp_unslash($_POST['password_confirmation']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : '';
        $firstNameLength = function_exists('mb_strlen') ? mb_strlen($firstName) : strlen($firstName);

        if ($firstNameLength < 2) {
            $errors->add('registration-error-name', 'Вкажи ім’я щонайменше з двох символів.');
        }

        if (strlen($password) < 6) {
            $errors->add('registration-error-password', 'Пароль має містити щонайменше 6 символів.');
        }

        if (!hash_equals($password, $confirmation)) {
            $errors->add('registration-error-password-confirmation', 'Паролі не збігаються.');
        }

        return $errors;
    }

    /** @param array<string, mixed> $customerData */
    public function addCustomerName(array $customerData): array
    {
        if (!isset($_POST['maruderm_registration'], $_POST['first_name'])) {
            return $customerData;
        }

        $firstName = sanitize_text_field(wp_unslash((string) $_POST['first_name']));
        $customerData['first_name'] = $firstName;
        $customerData['display_name'] = $firstName;

        return $customerData;
    }
}
