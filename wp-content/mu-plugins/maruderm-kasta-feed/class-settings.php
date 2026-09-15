<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Settings
{
    public const OPTION = 'maruderm_kasta_settings';
    public const STATUS = 'maruderm_kasta_status';
    public const MANUAL_HOOK = 'maruderm_kasta_manual';
    public const AUTO_HOOK = 'maruderm_kasta_refresh';

    public function get(): array
    {
        $saved = get_option(self::OPTION, []);
        return array_merge(['enabled' => false, 'automatic' => false, 'key' => '', 'max_age_hours' => 24], is_array($saved) ? $saved : []);
    }

    public function save(array $input, bool $rotate = false): void
    {
        $settings = $this->get();
        $settings['enabled'] = ! empty($input['enabled']);
        $settings['automatic'] = $settings['enabled'] && ! empty($input['automatic']);
        $settings['max_age_hours'] = max(1, min(48, (int) ($input['max_age_hours'] ?? 24)));
        if ($rotate || ! preg_match('/^[a-f0-9]{64}$/D', $settings['key'])) {
            $settings['key'] = bin2hex(random_bytes(32));
        }
        update_option(self::OPTION, $settings, false);
        if (! $settings['enabled']) {
            wp_clear_scheduled_hook(self::MANUAL_HOOK);
        }
        $this->schedule();
    }

    public function schedule(): void
    {
        $settings = $this->get();
        if (! $settings['enabled'] || ! $settings['automatic']) {
            wp_clear_scheduled_hook(self::AUTO_HOOK);
        } elseif (! wp_next_scheduled(self::AUTO_HOOK)) {
            $result = wp_schedule_event(time() + 60, 'maruderm_kasta_quarter_hour', self::AUTO_HOOK, [], true);
            if (is_wp_error($result) || ! $result) {
                throw new \RuntimeException('Не вдалося запланувати автоматичне оновлення.');
            }
        }
    }

    public function intervals(array $schedules): array
    {
        $schedules['maruderm_kasta_quarter_hour'] = ['interval' => 900, 'display' => 'Kasta: кожні 15 хвилин'];
        return $schedules;
    }

    public function url(): string
    {
        $key = $this->get()['key'];
        return $key !== '' ? home_url('/kasta-feed/' . $key . '/products.xml', 'https') : '';
    }
}
