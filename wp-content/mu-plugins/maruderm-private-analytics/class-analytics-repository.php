<?php

namespace Maruderm\Analytics;

use wpdb;

final class AnalyticsRepository
{
    private const PRODUCT_VIEWS_META = '_maruderm_product_views';

    public function __construct(private readonly wpdb $database)
    {
    }

    public function tableName(): string
    {
        return $this->database->prefix . 'maruderm_analytics_events';
    }

    public function install(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $this->tableName();
        $collate = $this->database->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            occurred_at datetime NOT NULL,
            session_hash char(64) NOT NULL,
            sequence_no int(10) unsigned NOT NULL DEFAULT 0,
            event_type varchar(32) NOT NULL,
            path varchar(255) NOT NULL DEFAULT '',
            referrer_path varchar(255) NOT NULL DEFAULT '',
            referrer_host varchar(191) NOT NULL DEFAULT '',
            object_type varchar(24) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            object_name varchar(191) NOT NULL DEFAULT '',
            category_name varchar(191) NOT NULL DEFAULT '',
            checkout_step varchar(32) NOT NULL DEFAULT '',
            logged_in tinyint(1) unsigned NOT NULL DEFAULT 0,
            scroll_depth tinyint(3) unsigned NOT NULL DEFAULT 0,
            duration_ms int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY occurred_at (occurred_at),
            KEY session_hash (session_hash),
            KEY session_sequence (session_hash, sequence_no),
            KEY event_type (event_type),
            KEY object_lookup (object_type, object_id),
            KEY checkout_step (checkout_step)
        ) {$collate};";

        dbDelta($sql);
    }

    /** @param array<string, mixed> $event */
    public function record(array $event, bool $loggedIn): ?int
    {
        $sessionHash = hash('sha256', (string) $event['sessionId']);
        $eventType = sanitize_key((string) $event['eventType']);
        $objectId = absint($event['objectId'] ?? 0);

        if ($eventType === 'product_view' && $objectId > 0 && $this->hasRecentProductView($sessionHash, $objectId)) {
            return $this->productViews($objectId);
        }

        if ($this->hasRapidDuplicate($sessionHash, $eventType, (string) ($event['path'] ?? ''), (string) ($event['checkoutStep'] ?? ''))) {
            return null;
        }

        $inserted = $this->database->insert(
            $this->tableName(),
            [
                'occurred_at' => current_time('mysql', true),
                'session_hash' => $sessionHash,
                'sequence_no' => min(100000, absint($event['sequenceNo'] ?? 0)),
                'event_type' => $eventType,
                'path' => $this->cleanPath((string) ($event['path'] ?? '')),
                'referrer_path' => $this->cleanOptionalPath((string) ($event['referrerPath'] ?? '')),
                'referrer_host' => $this->cleanHost((string) ($event['referrerHost'] ?? '')),
                'object_type' => sanitize_key((string) ($event['objectType'] ?? '')),
                'object_id' => $objectId,
                'object_name' => sanitize_text_field((string) ($event['objectName'] ?? '')),
                'category_name' => sanitize_text_field((string) ($event['categoryName'] ?? '')),
                'checkout_step' => sanitize_key((string) ($event['checkoutStep'] ?? '')),
                'logged_in' => $loggedIn ? 1 : 0,
                'scroll_depth' => min(100, absint($event['scrollDepth'] ?? 0)),
                'duration_ms' => min(3600000, absint($event['durationMs'] ?? 0)),
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d']
        );

        if ($inserted && $eventType === 'product_view' && $objectId > 0) {
            $count = $this->productViews($objectId) + 1;
            update_post_meta($objectId, self::PRODUCT_VIEWS_META, $count);

            return $count;
        }

        return null;
    }

    public function productViews(int $productId): int
    {
        return max(0, (int) get_post_meta($productId, self::PRODUCT_VIEWS_META, true));
    }

    public function purgeExpired(int $days = 90): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days);

        return (int) $this->database->query(
            $this->database->prepare("DELETE FROM {$this->tableName()} WHERE occurred_at < %s", $cutoff)
        );
    }

    /** @return array<string, mixed> */
    public function report(int $days): array
    {
        $table = $this->tableName();
        $since = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days);
        $where = $this->database->prepare('occurred_at >= %s', $since);
        $sessions = (int) $this->database->get_var("SELECT COUNT(DISTINCT session_hash) FROM {$table} WHERE {$where}");
        $pageViews = (int) $this->database->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where} AND event_type = 'page_view'");
        $engaged = (int) $this->database->get_var("SELECT COUNT(DISTINCT session_hash) FROM {$table} WHERE {$where} AND event_type IN ('engagement', 'scroll_depth', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_step', 'checkout_completed')");

        return [
            'days' => $days,
            'sessions' => $sessions,
            'page_views' => $pageViews,
            'engaged_sessions' => $engaged,
            'bounce_rate' => $sessions > 0 ? round((($sessions - $engaged) / $sessions) * 100, 1) : 0,
            'product_views' => $this->eventCount($where, 'product_view'),
            'add_to_cart' => $this->eventCount($where, 'add_to_cart'),
            'checkout_started' => $this->eventCount($where, 'checkout_started'),
            'checkout_completed' => $this->eventCount($where, 'checkout_completed'),
            'top_pages' => $this->topRows($where, 'page_view', 'path'),
            'top_products' => $this->topRows($where, 'product_view', 'object_name'),
            'top_categories' => $this->topRows($where, 'category_view', 'category_name'),
            'checkout_steps' => $this->topRows($where, 'checkout_step', 'checkout_step', 10),
            'scroll_depth' => $this->scrollRows($where),
            'login_split' => $this->loginRows($where),
            'sessions_list' => $this->sessionRows($where),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function journey(string $sessionHash, int $days): array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $sessionHash)) {
            return [];
        }

        $since = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days);

        return $this->database->get_results($this->database->prepare(
            "SELECT occurred_at, sequence_no, event_type, path, referrer_path, referrer_host, object_name, category_name, checkout_step, scroll_depth, duration_ms, logged_in FROM {$this->tableName()} WHERE session_hash = %s AND occurred_at >= %s ORDER BY sequence_no ASC, id ASC",
            $sessionHash,
            $since
        ), ARRAY_A);
    }

    private function hasRecentProductView(string $sessionHash, int $productId): bool
    {
        $table = $this->tableName();
        $since = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS);

        return (bool) $this->database->get_var($this->database->prepare(
            "SELECT id FROM {$table} WHERE event_type = 'product_view' AND session_hash = %s AND object_id = %d AND occurred_at >= %s LIMIT 1",
            $sessionHash,
            $productId,
            $since
        ));
    }

    private function hasRapidDuplicate(string $sessionHash, string $eventType, string $path, string $checkoutStep): bool
    {
        $table = $this->tableName();
        $since = gmdate('Y-m-d H:i:s', time() - 5);

        return (bool) $this->database->get_var($this->database->prepare(
            "SELECT id FROM {$table} WHERE session_hash = %s AND event_type = %s AND path = %s AND checkout_step = %s AND occurred_at >= %s LIMIT 1",
            $sessionHash,
            $eventType,
            $this->cleanPath($path),
            sanitize_key($checkoutStep),
            $since
        ));
    }

    private function cleanPath(string $path): string
    {
        $path = (string) wp_parse_url($path, PHP_URL_PATH);
        $path = '/' . ltrim($path, '/');

        return substr(sanitize_text_field($path), 0, 255);
    }

    private function cleanOptionalPath(string $path): string
    {
        return $path === '' ? '' : $this->cleanPath($path);
    }

    private function cleanHost(string $host): string
    {
        $host = strtolower(sanitize_text_field($host));

        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/', $host)
            ? substr($host, 0, 191)
            : '';
    }

    private function eventCount(string $where, string $eventType): int
    {
        return (int) $this->database->get_var(
            $this->database->prepare("SELECT COUNT(*) FROM {$this->tableName()} WHERE {$where} AND event_type = %s", $eventType)
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function topRows(string $where, string $eventType, string $column, int $limit = 8): array
    {
        $allowed = ['path', 'object_name', 'category_name', 'checkout_step'];
        if (! in_array($column, $allowed, true)) {
            return [];
        }

        return $this->database->get_results(
            $this->database->prepare(
                "SELECT {$column} AS label, COUNT(*) AS total FROM {$this->tableName()} WHERE {$where} AND event_type = %s AND {$column} <> '' GROUP BY {$column} ORDER BY total DESC LIMIT %d",
                $eventType,
                $limit
            ),
            ARRAY_A
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function scrollRows(string $where): array
    {
        return $this->database->get_results(
            "SELECT scroll_depth AS label, COUNT(DISTINCT session_hash) AS total FROM {$this->tableName()} WHERE {$where} AND event_type = 'scroll_depth' GROUP BY scroll_depth ORDER BY scroll_depth ASC",
            ARRAY_A
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function loginRows(string $where): array
    {
        return $this->database->get_results(
            "SELECT logged_in AS label, COUNT(DISTINCT session_hash) AS total FROM {$this->tableName()} WHERE {$where} GROUP BY logged_in ORDER BY logged_in ASC",
            ARRAY_A
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function sessionRows(string $where): array
    {
        return $this->database->get_results(
            "SELECT session_hash, MAX(logged_in) AS logged_in, MIN(occurred_at) AS started_at, MAX(occurred_at) AS ended_at, COUNT(*) AS actions, COUNT(DISTINCT path) AS pages, SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(path, '') ORDER BY sequence_no ASC, id ASC SEPARATOR '||'), '||', 1) AS entry_path, SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(path, '') ORDER BY sequence_no DESC, id DESC SEPARATOR '||'), '||', 1) AS exit_path, SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(referrer_host, '') ORDER BY sequence_no ASC, id ASC SEPARATOR '||'), '||', 1) AS referrer_host FROM {$this->tableName()} WHERE {$where} GROUP BY session_hash ORDER BY ended_at DESC LIMIT 100",
            ARRAY_A
        );
    }
}
