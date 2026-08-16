<?php

declare(strict_types=1);

namespace Maruderm\Account;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Registers avatar image dimensions and handles authenticated avatar mutations. */
final class AccountAvatars implements Registrable
{
    use Loadable;

    public const AJAX_ACTION = 'maruderm_update_account_avatar';
    public const NONCE_ACTION = 'maruderm-account-avatar';

    private AccountAvatarService $service;

    public function __construct(?AccountAvatarService $service = null)
    {
        $this->service = $service ?? new AccountAvatarService();
    }

    public function register(): void
    {
        add_action('after_setup_theme', [$this, 'registerImageSize']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'update']);
    }

    public function registerImageSize(): void
    {
        add_image_size('maruderm-account-avatar', 512, 512, true);
    }

    public function update(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Увійди в акаунт, щоб змінити фото.'], 401);
        }

        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => 'Сесію форми завершено. Онови сторінку та спробуй ще раз.'], 403);
        }

        $userId = get_current_user_id();
        $operation = sanitize_key(wp_unslash((string) ($_POST['operation'] ?? 'upload')));

        if ($operation === 'remove') {
            $this->service->remove($userId);
            wp_send_json_success(['message' => 'Фото видалено. Показуємо ініціали.']);
        }

        $file = isset($_FILES['avatar']) && is_array($_FILES['avatar']) ? $_FILES['avatar'] : [];
        $url = $this->service->upload($userId, $file);

        if (is_wp_error($url)) {
            wp_send_json_error(['message' => $url->get_error_message()], 422);
        }

        wp_send_json_success([
            'url' => $url,
            'message' => 'Фото профілю оновлено.',
        ]);
    }
}
