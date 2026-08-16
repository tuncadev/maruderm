<?php

declare(strict_types=1);

namespace Maruderm\Account;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns persistent WordPress media used as a customer's account avatar. */
final class AccountAvatarService
{
    private const META_KEY = '_maruderm_account_avatar_attachment_id';
    private const MAX_FILE_SIZE = 5 * MB_IN_BYTES;
    private const ALLOWED_MIMES = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public function attachmentId(int $userId): int
    {
        $attachmentId = absint(get_user_meta($userId, self::META_KEY, true));

        return $attachmentId > 0 && get_post_type($attachmentId) === 'attachment' ? $attachmentId : 0;
    }

    public function url(int $userId): string
    {
        $attachmentId = $this->attachmentId($userId);
        $url = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, 'maruderm-account-avatar') : false;

        return is_string($url) ? $url : '';
    }

    /** @param array<string, mixed> $file */
    public function upload(int $userId, array $file): string|\WP_Error
    {
        if ($userId <= 0 || empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return new \WP_Error('invalid_avatar_upload', 'Не вдалося отримати зображення.');
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return new \WP_Error('avatar_upload_error', 'Не вдалося завантажити зображення.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_FILE_SIZE) {
            return new \WP_Error('avatar_too_large', 'Зображення має бути не більше 5 МБ.');
        }

        $checked = wp_check_filetype_and_ext(
            (string) $file['tmp_name'],
            sanitize_file_name((string) ($file['name'] ?? 'avatar')),
            self::ALLOWED_MIMES
        );

        if (!in_array((string) ($checked['type'] ?? ''), array_values(self::ALLOWED_MIMES), true)) {
            return new \WP_Error('invalid_avatar_type', 'Обери зображення у форматі JPG, PNG або WebP.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachmentId = media_handle_upload('avatar', 0, [], [
            'test_form' => false,
            'mimes' => self::ALLOWED_MIMES,
        ]);

        if (is_wp_error($attachmentId)) {
            return $attachmentId;
        }

        $previousId = $this->attachmentId($userId);
        update_user_meta($userId, self::META_KEY, $attachmentId);

        if ($previousId > 0 && $previousId !== $attachmentId && (int) get_post_field('post_author', $previousId) === $userId) {
            wp_delete_attachment($previousId, true);
        }

        return $this->url($userId);
    }

    public function remove(int $userId): void
    {
        $attachmentId = $this->attachmentId($userId);
        delete_user_meta($userId, self::META_KEY);

        if ($attachmentId > 0 && (int) get_post_field('post_author', $attachmentId) === $userId) {
            wp_delete_attachment($attachmentId, true);
        }
    }
}
