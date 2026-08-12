<?php

namespace SocialAuth;

use InvalidArgumentException;
use RuntimeException;

class AuthCore
{
    /**
     * @param array<string, string> $userData
     */
    public function authenticate(array $userData, string $mode = 'login'): int
    {
        $email = sanitize_email((string) ($userData['email'] ?? ''));
        $provider = sanitize_key((string) ($userData['provider'] ?? ''));
        $providerId = sanitize_text_field((string) ($userData['provider_id'] ?? ''));
        $name = sanitize_text_field((string) ($userData['name'] ?? ''));
        $avatar = esc_url_raw((string) ($userData['avatar'] ?? ''));
        $mode = sanitize_key($mode);

        if ($provider === '' || $providerId === '') {
            throw new InvalidArgumentException('Provider identity is missing.');
        }

        if (! in_array($mode, ['login', 'register'], true)) {
            throw new InvalidArgumentException('Invalid auth mode.');
        }

        if ($mode === 'register' && $email === '') {
            throw new InvalidArgumentException('Email is required by provider response.');
        }

        $user = $email !== '' ? get_user_by('email', $email) : false;
        $metaKey = 'social_auth_provider_id_' . $provider;

        if ($mode === 'login') {
            if (! $user) {
                $linkedUserId = $this->findUserIdByProviderId($metaKey, $providerId);
                if ($linkedUserId > 0) {
                    $user = get_user_by('id', $linkedUserId);
                }
            }

            if (! $user) {
                throw new RuntimeException('No account is linked to this social identity.');
            }

            $userId = (int) $user->ID;
            $storedProviderId = (string) get_user_meta($userId, $metaKey, true);

            if ($storedProviderId === '' || ! hash_equals($storedProviderId, $providerId)) {
                throw new RuntimeException('This account is not linked to this social provider.');
            }
        } elseif (! $user) {
            $username = $this->generateUniqueUsername($email, $name);
            $password = wp_generate_password(64, true, true);
            $displayName = $name !== '' ? $name : $username;

            $userId = wp_insert_user([
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'display_name' => $displayName,
                'role' => get_option('default_role', 'subscriber'),
            ]);

            if (is_wp_error($userId)) {
                throw new RuntimeException($userId->get_error_message());
            }
        } else {
            $userId = (int) $user->ID;
            $storedProviderId = (string) get_user_meta($userId, $metaKey, true);

            if ($storedProviderId === '') {
                throw new RuntimeException('An account with this email already exists.');
            }

            if (! hash_equals($storedProviderId, $providerId)) {
                throw new RuntimeException('This email is linked to a different social identity.');
            }
        }

        update_user_meta($userId, 'social_auth_provider', $provider);
        update_user_meta($userId, $metaKey, $providerId);

        if ($avatar !== '') {
            update_user_meta($userId, 'social_auth_avatar', $avatar);
        }

        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        $updatedUser = get_userdata($userId);
        if ($updatedUser) {
            do_action('wp_login', $updatedUser->user_login, $updatedUser);
        }

        return $userId;
    }

    private function generateUniqueUsername(string $email, string $name): string
    {
        $emailParts = explode('@', $email);
        $emailPrefix = $emailParts[0] ?? '';
        $base = sanitize_user($name !== '' ? $name : $emailPrefix, true);
        $base = $base !== '' ? $base : 'social_user';
        $candidate = $base;
        $suffix = 1;

        while (username_exists($candidate)) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function findUserIdByProviderId(string $metaKey, string $providerId): int
    {
        $users = get_users([
            'fields' => 'ids',
            'number' => 1,
            'meta_key' => $metaKey,
            'meta_value' => $providerId,
        ]);

        if (! is_array($users) || $users === []) {
            return 0;
        }

        return (int) $users[0];
    }
}
