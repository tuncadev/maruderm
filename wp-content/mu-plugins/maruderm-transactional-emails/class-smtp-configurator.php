<?php
/**
 * Configures wp_mail() with the project mailbox credentials stored as constants.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_SMTP_Configurator
{
    public function register(): void
    {
        add_action('phpmailer_init', [$this, 'configure']);
        add_filter('wp_mail_from', [$this, 'from_email']);
        add_filter('wp_mail_from_name', [$this, 'from_name']);
    }

    public function configure($mailer): void
    {
        if (! $this->is_configured()) {
            return;
        }

        $mailer->isSMTP();
        $mailer->Host = (string) constant('MARUDERM_SMTP_HOST');
        $mailer->Port = (int) constant('MARUDERM_SMTP_PORT');
        $mailer->SMTPAuth = true;
        $mailer->Username = (string) constant('MARUDERM_SMTP_USERNAME');
        $mailer->Password = (string) constant('MARUDERM_SMTP_PASSWORD');
        $mailer->SMTPSecure = 'ssl';
        $mailer->SMTPAutoTLS = true;
    }

    public function from_email(string $email): string
    {
        return defined('MARUDERM_SMTP_FROM_EMAIL')
            ? sanitize_email((string) constant('MARUDERM_SMTP_FROM_EMAIL'))
            : $email;
    }

    public function from_name(string $name): string
    {
        return defined('MARUDERM_SMTP_FROM_NAME')
            ? sanitize_text_field((string) constant('MARUDERM_SMTP_FROM_NAME'))
            : $name;
    }

    private function is_configured(): bool
    {
        foreach (['MARUDERM_SMTP_HOST', 'MARUDERM_SMTP_PORT', 'MARUDERM_SMTP_USERNAME', 'MARUDERM_SMTP_PASSWORD'] as $constant) {
            if (! defined($constant) || trim((string) constant($constant)) === '') {
                return false;
            }
        }

        return true;
    }
}
