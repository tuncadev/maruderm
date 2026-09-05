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
        $mailer->CharSet = 'UTF-8';

        $from_email = $this->from_email((string) $mailer->From);

        if ($from_email !== '') {
            $mailer->Sender = $from_email;
        }

        if ($mailer->ContentType === 'text/html' && trim((string) $mailer->AltBody) === '') {
            $mailer->AltBody = $this->plain_text((string) $mailer->Body);
        }
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

    private function plain_text(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html);
        $text = preg_replace('/<\/(p|div|tr|li|h[1-6])>/i', "\n", (string) $text);
        $text = html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\t ]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", (string) $text);

        return trim((string) $text);
    }
}
