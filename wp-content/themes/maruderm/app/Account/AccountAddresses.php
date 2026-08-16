<?php

declare(strict_types=1);

namespace Maruderm\Account;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Handles authenticated account address mutations. */
final class AccountAddresses implements Registrable
{
    use Loadable;

    public const AJAX_ACTION = 'maruderm_save_account_address';
    public const NONCE_ACTION = 'maruderm-account-address';

    private AccountAddressService $service;

    public function __construct(?AccountAddressService $service = null)
    {
        $this->service = $service ?? new AccountAddressService();
    }

    public function register(): void
    {
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'save']);
    }

    public function save(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Увійди в акаунт, щоб зберегти адресу.'], 401);
        }

        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => 'Сесію форми завершено. Онови сторінку та спробуй ще раз.'], 403);
        }

        try {
            $address = $this->service->add(
                get_current_user_id(),
                sanitize_text_field(wp_unslash((string) ($_POST['type'] ?? ''))),
                sanitize_text_field(wp_unslash((string) ($_POST['city'] ?? ''))),
                sanitize_text_field(wp_unslash((string) ($_POST['location'] ?? '')))
            );
        } catch (\InvalidArgumentException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        }

        wp_send_json_success([
            'address' => $address,
            'message' => 'Нову адресу додано.',
        ]);
    }
}
