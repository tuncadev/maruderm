<?php

namespace Maruderm\Analytics;

use WP_User;

final class AnalyticsExclusionPolicy
{
    private const EXCLUDED_IPS_OPTION = 'maruderm_analytics_excluded_ips';
    private const EXCLUDED_ROLES_OPTION = 'maruderm_analytics_excluded_roles';

    public function isCurrentRequestExcluded(): bool
    {
        $ip = $this->requestIp();

        if ($ip !== '' && in_array($ip, $this->excludedIps(), true)) {
            return true;
        }

        $user = wp_get_current_user();

        return $user->exists() && array_intersect($user->roles, $this->excludedRoles()) !== [];
    }

    /** @return string[] */
    public function excludedIps(): array
    {
        $value = get_option(self::EXCLUDED_IPS_OPTION, []);

        return is_array($value) ? $this->sanitizeIps($value) : [];
    }

    /** @return string[] */
    public function excludedRoles(): array
    {
        $value = get_option(self::EXCLUDED_ROLES_OPTION, null);

        if ($value === null) {
            return ['administrator'];
        }

        return is_array($value) ? $this->sanitizeRoles($value) : [];
    }

    public function currentRequestIp(): string
    {
        return $this->requestIp();
    }

    /** @param string[] $ips @param string[] $roles */
    public function update(array $ips, array $roles): void
    {
        update_option(self::EXCLUDED_IPS_OPTION, $this->sanitizeIps($ips), false);
        update_option(self::EXCLUDED_ROLES_OPTION, $this->sanitizeRoles($roles), false);
    }

    /** @return string[] */
    public function parseIpInput(string $input): array
    {
        return $this->sanitizeIps(preg_split('/[\s,]+/', $input) ?: []);
    }

    /** @param mixed[] $values @return string[] */
    private function sanitizeIps(array $values): array
    {
        $ips = array_filter(array_map(
            static fn ($value): string => filter_var(trim((string) $value), FILTER_VALIDATE_IP) ? trim((string) $value) : '',
            $values
        ));

        return array_values(array_unique($ips));
    }

    /** @param mixed[] $values @return string[] */
    private function sanitizeRoles(array $values): array
    {
        $availableRoles = array_keys(wp_roles()->roles);
        $roles = array_map('sanitize_key', $values);

        return array_values(array_intersect(array_unique($roles), $availableRoles));
    }

    private function requestIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_X_MARUDERM_VISITOR_IP'] ?? '',
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_REAL_IP'] ?? '',
            explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            $ip = trim((string) $candidate);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '';
    }
}
