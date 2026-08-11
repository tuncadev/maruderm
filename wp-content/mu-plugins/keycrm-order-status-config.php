<?php
/**
 * Configurable KeyCRM to WooCommerce order-status mappings.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_KeyCRM_Status_Config
{
    public const MAPPINGS_OPTION = 'maruderm_keycrm_status_mappings';
    public const DICTIONARY_OPTION = 'maruderm_keycrm_status_dictionary';

    private const STATUS_GROUP_MAP = [
        1 => 'pending',
        2 => 'processing',
        3 => 'processing',
        4 => 'processing',
        5 => 'completed',
        6 => 'cancelled',
    ];

    private const FALLBACK_STATUSES = [
        'pending' => 'Pending payment',
        'processing' => 'Processing',
        'on-hold' => 'On hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'failed' => 'Failed',
    ];

    private const DEFAULT_MAPPINGS = [
        1 => [
            'name' => 'new',
            'include' => true,
            'label' => 'New',
            'slug' => 'keycrm-new',
            'fallback' => 'pending',
        ],
        2 => [
            'name' => 'Confirmed',
            'include' => true,
            'label' => 'Confirmed',
            'slug' => 'confirmed',
            'fallback' => 'processing',
        ],
        4 => [
            'name' => 'waiting_for_prepayment',
            'include' => true,
            'label' => 'Waiting for Prepayment',
            'slug' => 'wait-prepayment',
            'fallback' => 'processing',
        ],
        8 => [
            'name' => 'TTN created',
            'include' => true,
            'label' => 'TTN Created',
            'slug' => 'ttn-created',
            'fallback' => 'processing',
        ],
        9 => [
            'name' => 'Ready to send',
            'include' => true,
            'label' => 'Ready to Send',
            'slug' => 'ready-to-send',
            'fallback' => 'processing',
        ],
        10 => [
            'name' => 'departing',
            'include' => true,
            'label' => 'Departing',
            'slug' => 'departing',
            'fallback' => 'processing',
        ],
        12 => [
            'name' => 'completed',
            'include' => false,
            'label' => 'Completed',
            'slug' => 'keycrm-12',
            'fallback' => 'completed',
        ],
        19 => [
            'name' => 'canceled',
            'include' => false,
            'label' => 'Cancelled',
            'slug' => 'keycrm-19',
            'fallback' => 'cancelled',
        ],
    ];

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(): void
    {
        add_action('init', [$this, 'register_order_statuses']);
        add_filter('wc_order_statuses', [$this, 'add_order_statuses']);
        add_filter('woocommerce_order_is_paid', [$this, 'preserve_paid_state'], 10, 2);
    }

    public function register_order_statuses(): void
    {
        foreach ($this->custom_statuses() as $status => $label) {
            register_post_status(
                'wc-' . $status,
                [
                    'label' => $label,
                    'public' => false,
                    'exclude_from_search' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop(
                        $label . ' <span class="count">(%s)</span>',
                        $label . ' <span class="count">(%s)</span>'
                    ),
                ]
            );
        }
    }

    public function add_order_statuses(array $statuses): array
    {
        $ordered_statuses = [];

        foreach ($this->custom_statuses() as $status => $label) {
            $ordered_statuses['wc-' . $status] = $label;
        }

        foreach ($statuses as $status => $label) {
            $ordered_statuses[$status] = $label;
        }

        return $ordered_statuses;
    }

    public function preserve_paid_state(bool $is_paid, $order): bool
    {
        if ($is_paid || ! $order instanceof WC_Order) {
            return $is_paid;
        }

        return isset($this->custom_statuses()[$order->get_status()])
            && $order->get_date_paid() !== null;
    }

    public function target_status(int $status_id, int $status_group_id): string
    {
        $mapping = $this->mappings()[$status_id] ?? null;

        if (is_array($mapping)) {
            return ! empty($mapping['include'])
                ? sanitize_key((string) ($mapping['slug'] ?? ''))
                : $this->sanitize_fallback((string) ($mapping['fallback'] ?? 'processing'));
        }

        $group_map = apply_filters('maruderm_keycrm_status_group_map', self::STATUS_GROUP_MAP);

        return is_array($group_map)
            ? sanitize_key((string) ($group_map[$status_group_id] ?? ''))
            : '';
    }

    public function mappings(): array
    {
        $stored = get_option(self::MAPPINGS_OPTION, []);
        $mappings = [];

        foreach (self::DEFAULT_MAPPINGS as $status_id => $mapping) {
            $mappings[$status_id] = $this->normalize_mapping(
                $status_id,
                $mapping,
                (string) ($mapping['name'] ?? '')
            );
        }

        if (is_array($stored)) {
            foreach ($stored as $status_id => $mapping) {
                $status_id = absint($status_id);

                if ($status_id <= 0 || ! is_array($mapping)) {
                    continue;
                }

                $mappings[$status_id] = $this->normalize_mapping(
                    $status_id,
                    $mapping,
                    (string) ($mapping['name'] ?? '')
                );
            }
        }

        $dictionary = $this->dictionary();

        if ($dictionary === []) {
            return $mappings;
        }

        return array_intersect_key($mappings, $dictionary);
    }

    public function custom_statuses(): array
    {
        $statuses = [];

        foreach ($this->mappings() as $mapping) {
            if (empty($mapping['registered'])) {
                continue;
            }

            $slug = sanitize_key((string) ($mapping['slug'] ?? ''));
            $label = sanitize_text_field((string) ($mapping['label'] ?? ''));

            if ($slug !== '' && $label !== '') {
                $statuses[$slug] = $label;
            }
        }

        return $statuses;
    }

    public function fallback_statuses(): array
    {
        return self::FALLBACK_STATUSES;
    }

    public function dictionary(): array
    {
        $cache = get_option(self::DICTIONARY_OPTION, []);
        $statuses = is_array($cache) && isset($cache['statuses']) && is_array($cache['statuses'])
            ? $cache['statuses']
            : [];
        $dictionary = [];

        foreach ($statuses as $status) {
            if (! is_array($status)) {
                continue;
            }

            $status_id = absint($status['id'] ?? 0);
            $name = sanitize_text_field((string) ($status['name'] ?? ''));

            if ($status_id > 0 && $name !== '') {
                $dictionary[$status_id] = [
                    'id' => $status_id,
                    'name' => $name,
                ];
            }
        }

        return $dictionary;
    }

    public function dictionary_fetched_at(): string
    {
        $cache = get_option(self::DICTIONARY_OPTION, []);

        return is_array($cache) ? sanitize_text_field((string) ($cache['fetched_at'] ?? '')) : '';
    }

    public function default_dictionary(): array
    {
        $dictionary = [];

        foreach (self::DEFAULT_MAPPINGS as $status_id => $mapping) {
            $dictionary[$status_id] = [
                'id' => $status_id,
                'name' => (string) $mapping['name'],
            ];
        }

        return $dictionary;
    }

    public function save_dictionary(array $statuses): void
    {
        $dictionary = [];

        foreach ($statuses as $status) {
            if (! is_array($status)) {
                continue;
            }

            $status_id = absint($status['id'] ?? 0);
            $name = sanitize_text_field((string) ($status['name'] ?? ''));

            if ($status_id > 0 && $name !== '') {
                $dictionary[$status_id] = [
                    'id' => $status_id,
                    'name' => $name,
                ];
            }
        }

        update_option(
            self::DICTIONARY_OPTION,
            [
                'statuses' => array_values($dictionary),
                'fetched_at' => gmdate('Y-m-d H:i:s'),
            ],
            false
        );
    }

    public function rows_for_dictionary(array $dictionary): array
    {
        $mappings = $this->mappings();
        $rows = [];

        foreach ($dictionary as $status_id => $status) {
            $status_id = absint($status_id);
            $name = is_array($status)
                ? sanitize_text_field((string) ($status['name'] ?? ''))
                : '';

            if ($status_id <= 0 || $name === '') {
                continue;
            }

            $rows[$status_id] = $this->normalize_mapping(
                $status_id,
                $mappings[$status_id] ?? [],
                $name
            );
        }

        return $rows;
    }

    public function save_mappings(array $submitted, array $dictionary): array
    {
        $current = $this->mappings();
        $saved = [];

        foreach ($dictionary as $status_id => $status) {
            $status_id = absint($status_id);
            $name = is_array($status)
                ? sanitize_text_field((string) ($status['name'] ?? ''))
                : '';

            if ($status_id <= 0 || $name === '') {
                continue;
            }

            $input = isset($submitted[$status_id]) && is_array($submitted[$status_id])
                ? $submitted[$status_id]
                : [];
            $existing = $current[$status_id] ?? [];
            $slug = $this->normalize_slug(
                $status_id,
                (string) ($existing['slug'] ?? '')
            );
            $label = sanitize_text_field((string) ($input['label'] ?? ($existing['label'] ?? $name)));

            if ($label === '') {
                $label = $name;
            }

            $saved[$status_id] = [
                'name' => $name,
                'include' => isset($input['include']) && (string) $input['include'] === '1',
                'label' => $label,
                'slug' => $slug,
                'fallback' => $this->sanitize_fallback((string) ($input['fallback'] ?? 'processing')),
            ];
            $saved[$status_id]['registered'] = $saved[$status_id]['include']
                || $this->status_has_orders($slug);
        }

        update_option(self::MAPPINGS_OPTION, $saved, false);

        return $saved;
    }

    private function normalize_mapping(int $status_id, array $mapping, string $name): array
    {
        $default = self::DEFAULT_MAPPINGS[$status_id] ?? [];
        $resolved_name = sanitize_text_field((string) ($mapping['name'] ?? ($default['name'] ?? $name)));
        $label = sanitize_text_field((string) ($mapping['label'] ?? ($default['label'] ?? $resolved_name)));

        return [
            'name' => $resolved_name !== '' ? $resolved_name : $name,
            'include' => array_key_exists('include', $mapping)
                ? (bool) $mapping['include']
                : (bool) ($default['include'] ?? false),
            'registered' => array_key_exists('registered', $mapping)
                ? (bool) $mapping['registered']
                : (bool) ($mapping['include'] ?? ($default['include'] ?? false)),
            'label' => $label !== '' ? $label : $name,
            'slug' => $this->normalize_slug(
                $status_id,
                (string) ($mapping['slug'] ?? ($default['slug'] ?? ''))
            ),
            'fallback' => $this->sanitize_fallback(
                (string) ($mapping['fallback'] ?? ($default['fallback'] ?? 'processing'))
            ),
        ];
    }

    private function normalize_slug(int $status_id, string $slug): string
    {
        $slug = sanitize_key($slug);

        if (str_starts_with($slug, 'wc-')) {
            $slug = substr($slug, 3);
        }

        if ($slug === '' || strlen('wc-' . $slug) > 20) {
            return 'keycrm-' . $status_id;
        }

        return $slug;
    }

    private function sanitize_fallback(string $fallback): string
    {
        $fallback = sanitize_key($fallback);

        return isset(self::FALLBACK_STATUSES[$fallback]) ? $fallback : 'processing';
    }

    private function status_has_orders(string $status): bool
    {
        if (! function_exists('wc_get_orders')) {
            return false;
        }

        return wc_get_orders([
            'limit' => 1,
            'return' => 'ids',
            'status' => [$status],
        ]) !== [];
    }
}

Maruderm_KeyCRM_Status_Config::instance()->register();
