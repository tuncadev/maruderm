<?php
/**
 * WooCommerce admin page for KeyCRM order-status mappings.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('Maruderm_KeyCRM_Status_Config')) {
    return;
}

final class Maruderm_KeyCRM_Status_Settings_Page
{
    private const API_BASE_URL = 'https://openapi.keycrm.app/v1';
    private const INTEGRATION_OPTION = 'woocommerce_integration-keycrm_settings';
    private const PAGE_SLUG = 'maruderm-keycrm-statuses';
    private const SAVE_ACTION = 'maruderm_keycrm_save_status_settings';
    private const REFRESH_ACTION = 'maruderm_keycrm_refresh_statuses';
    private const CAPABILITY = 'manage_woocommerce';

    private Maruderm_KeyCRM_Status_Config $config;
    private string $page_hook = '';

    public function __construct(Maruderm_KeyCRM_Status_Config $config)
    {
        $this->config = $config;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_page'], 40);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_' . self::SAVE_ACTION, [$this, 'handle_save']);
        add_action('admin_post_' . self::REFRESH_ACTION, [$this, 'handle_refresh']);
    }

    public function register_page(): void
    {
        $this->page_hook = (string) add_submenu_page(
            'woocommerce',
            'KeyCRM Status Mapping',
            'KeyCRM Statuses',
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== $this->page_hook) {
            return;
        }

        $css_path = __DIR__ . '/assets/keycrm-order-status-settings.css';
        $js_path = __DIR__ . '/assets/keycrm-order-status-settings.js';

        wp_enqueue_style(
            'maruderm-keycrm-status-settings',
            plugins_url('assets/keycrm-order-status-settings.css', __FILE__),
            [],
            is_file($css_path) ? (string) filemtime($css_path) : null
        );
        wp_enqueue_script(
            'maruderm-keycrm-status-settings',
            plugins_url('assets/keycrm-order-status-settings.js', __FILE__),
            [],
            is_file($js_path) ? (string) filemtime($js_path) : null,
            true
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'maruderm'));
        }

        $dictionary = $this->config->dictionary();
        $load_error = null;

        if ($dictionary === []) {
            $statuses = $this->fetch_statuses();

            if (is_wp_error($statuses)) {
                $load_error = $statuses;
                $dictionary = $this->config->default_dictionary();
            } else {
                $this->config->save_dictionary($statuses);
                $dictionary = $this->config->dictionary();
            }
        }

        $rows = $this->config->rows_for_dictionary($dictionary);
        $included_count = count(array_filter($rows, static fn (array $row): bool => ! empty($row['include'])));
        $fallback_count = count($rows) - $included_count;
        $fetched_at = $this->config->dictionary_fetched_at();
        ?>
        <div class="wrap mks-page">
            <section class="mks-hero">
                <div class="mks-hero__content">
                    <div class="mks-eyebrow">
                        <span class="mks-live-dot" aria-hidden="true"></span>
                        KeyCRM connection
                    </div>
                    <h1>Order status mapping</h1>
                    <p>Choose which KeyCRM statuses become native WooCommerce statuses and where the rest should fall back.</p>
                </div>
                <form class="mks-refresh" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::REFRESH_ACTION); ?>">
                    <?php wp_nonce_field(self::REFRESH_ACTION); ?>
                    <button class="mks-button mks-button--secondary" type="submit">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        Refresh from KeyCRM
                    </button>
                    <span class="mks-refresh__meta">
                        <?php echo $fetched_at !== '' ? esc_html('Last refreshed ' . get_date_from_gmt($fetched_at, 'M j, Y H:i')) : 'Not refreshed yet'; ?>
                    </span>
                </form>
            </section>

            <?php $this->render_notice($load_error); ?>

            <section class="mks-stats" aria-label="Mapping summary">
                <article class="mks-stat">
                    <span class="mks-stat__icon dashicons dashicons-randomize" aria-hidden="true"></span>
                    <span><strong><?php echo esc_html((string) count($rows)); ?></strong> KeyCRM statuses</span>
                </article>
                <article class="mks-stat">
                    <span class="mks-stat__icon mks-stat__icon--green dashicons dashicons-yes-alt" aria-hidden="true"></span>
                    <span><strong data-mks-included-count><?php echo esc_html((string) $included_count); ?></strong> custom Woo statuses</span>
                </article>
                <article class="mks-stat">
                    <span class="mks-stat__icon mks-stat__icon--slate dashicons dashicons-arrow-down-alt" aria-hidden="true"></span>
                    <span><strong data-mks-fallback-count><?php echo esc_html((string) $fallback_count); ?></strong> using fallback</span>
                </article>
            </section>

            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SAVE_ACTION); ?>">
                <?php wp_nonce_field(self::SAVE_ACTION); ?>

                <section class="mks-card">
                    <header class="mks-card__header">
                        <div>
                            <h2>Status rules</h2>
                            <p>Custom labels appear in WooCommerce order filters and status selectors. Technical slugs stay stable after saving.</p>
                        </div>
                        <span class="mks-secure"><span class="dashicons dashicons-lock" aria-hidden="true"></span> Server-side API</span>
                    </header>

                    <div class="mks-table-wrap">
                        <table class="mks-table">
                            <thead>
                                <tr>
                                    <th scope="col">KeyCRM status</th>
                                    <th scope="col">Include in WooCommerce</th>
                                    <th scope="col">WooCommerce label</th>
                                    <th scope="col">When excluded, fall back to</th>
                                    <th scope="col">Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $status_id => $row) : ?>
                                    <?php $this->render_row($status_id, $row); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <footer class="mks-card__footer">
                        <div class="mks-help">
                            <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                            Existing order statuses remain registered for history; KeyCRM automation must also send the selected status change.
                        </div>
                        <button class="mks-button mks-button--primary" type="submit">
                            <span class="dashicons dashicons-saved" aria-hidden="true"></span>
                            Save mappings
                        </button>
                    </footer>
                </section>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void
    {
        $this->guard_action(self::SAVE_ACTION);
        $dictionary = $this->config->dictionary();

        if ($dictionary === []) {
            $this->redirect('missing_dictionary');
        }

        $submitted = isset($_POST['mappings']) && is_array($_POST['mappings'])
            ? wp_unslash($_POST['mappings'])
            : [];
        $this->config->save_mappings($submitted, $dictionary);
        $this->redirect('saved');
    }

    public function handle_refresh(): void
    {
        $this->guard_action(self::REFRESH_ACTION);
        $statuses = $this->fetch_statuses();

        if (is_wp_error($statuses)) {
            $this->redirect('refresh_failed');
        }

        $this->config->save_dictionary($statuses);
        $this->redirect('refreshed');
    }

    public function fetch_statuses()
    {
        $api_token = $this->api_token();

        if ($api_token === '') {
            return new WP_Error('maruderm_keycrm_token_missing', 'The KeyCRM API token is not configured.');
        }

        $statuses = [];
        $page = 1;
        $total = 1;

        while (count($statuses) < $total && $page <= 20) {
            $response = wp_remote_get(
                self::API_BASE_URL . '/order/status?limit=50&page=' . $page,
                [
                    'timeout' => 20,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_token,
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if (is_wp_error($response)) {
                return new WP_Error('maruderm_keycrm_status_transport', 'KeyCRM status refresh could not connect.');
            }

            $status_code = (int) wp_remote_retrieve_response_code($response);
            $body = json_decode((string) wp_remote_retrieve_body($response), true);

            if ($status_code < 200 || $status_code >= 300 || ! is_array($body)) {
                return new WP_Error('maruderm_keycrm_status_response', 'KeyCRM returned an invalid status response.');
            }

            $data = isset($body['data']) && is_array($body['data']) ? $body['data'] : [];
            $total = max(0, absint($body['total'] ?? count($data)));

            foreach ($data as $status) {
                if (! is_array($status)) {
                    continue;
                }

                $status_id = absint($status['id'] ?? 0);
                $name = sanitize_text_field((string) ($status['name'] ?? ''));

                if ($status_id > 0 && $name !== '') {
                    $statuses[$status_id] = [
                        'id' => $status_id,
                        'name' => $name,
                    ];
                }
            }

            if ($data === []) {
                break;
            }

            $page++;
        }

        return array_values($statuses);
    }

    private function render_row(int $status_id, array $row): void
    {
        $included = ! empty($row['include']);
        $fallback = sanitize_key((string) ($row['fallback'] ?? 'processing'));
        $fallback_label = $this->config->fallback_statuses()[$fallback] ?? 'Processing';
        $result_label = $included ? (string) $row['label'] : $fallback_label;
        $result_slug = $included ? (string) $row['slug'] : $fallback;
        ?>
        <tr class="mks-row<?php echo $included ? ' is-included' : ''; ?>" data-mks-row>
            <td data-label="KeyCRM status">
                <div class="mks-keycrm-status">
                    <span class="mks-id">#<?php echo esc_html((string) $status_id); ?></span>
                    <strong><?php echo esc_html((string) $row['name']); ?></strong>
                </div>
            </td>
            <td data-label="Include in WooCommerce">
                <label class="mks-toggle">
                    <input
                        type="checkbox"
                        name="mappings[<?php echo esc_attr((string) $status_id); ?>][include]"
                        value="1"
                        data-mks-include
                        <?php checked($included); ?>
                    >
                    <span class="mks-toggle__track" aria-hidden="true"><span></span></span>
                    <span class="mks-toggle__label"><?php echo $included ? 'Included' : 'Excluded'; ?></span>
                </label>
            </td>
            <td data-label="WooCommerce label">
                <input
                    class="mks-input"
                    type="text"
                    name="mappings[<?php echo esc_attr((string) $status_id); ?>][label]"
                    value="<?php echo esc_attr((string) $row['label']); ?>"
                    maxlength="50"
                    data-mks-label
                >
            </td>
            <td data-label="Fallback">
                <select
                    class="mks-select"
                    name="mappings[<?php echo esc_attr((string) $status_id); ?>][fallback]"
                    data-mks-fallback
                >
                    <?php foreach ($this->config->fallback_statuses() as $slug => $label) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($fallback, $slug); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td data-label="Result">
                <div class="mks-result">
                    <span class="mks-result__type" data-mks-result-type><?php echo $included ? 'Custom' : 'Fallback'; ?></span>
                    <strong data-mks-result-label><?php echo esc_html($result_label); ?></strong>
                    <code
                        data-mks-result-slug
                        data-mks-custom-slug="wc-<?php echo esc_attr((string) $row['slug']); ?>"
                    >wc-<?php echo esc_html($result_slug); ?></code>
                </div>
            </td>
        </tr>
        <?php
    }

    private function render_notice($load_error): void
    {
        $message = sanitize_key((string) ($_GET['mks_message'] ?? ''));
        $notices = [
            'saved' => ['success', 'Status mappings saved. New rules apply to the next webhook request.'],
            'refreshed' => ['success', 'The KeyCRM status list was refreshed.'],
            'refresh_failed' => ['error', 'KeyCRM statuses could not be refreshed. The saved list is still available.'],
            'missing_dictionary' => ['error', 'Refresh the KeyCRM status list before saving mappings.'],
        ];

        if ($load_error instanceof WP_Error) {
            $notices[] = ['warning', 'Live KeyCRM statuses are unavailable, so the built-in mapping list is shown.'];
        }

        if ($message !== '' && isset($notices[$message])) {
            [$type, $text] = $notices[$message];
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($type),
                esc_html($text)
            );
        }

        if (isset($notices[0])) {
            [$type, $text] = $notices[0];
            printf(
                '<div class="notice notice-%1$s"><p>%2$s</p></div>',
                esc_attr($type),
                esc_html($text)
            );
        }
    }

    private function guard_action(string $action): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'maruderm'));
        }

        check_admin_referer($action);
    }

    private function redirect(string $message): void
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => self::PAGE_SLUG,
                    'mks_message' => sanitize_key($message),
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    private function api_token(): string
    {
        $settings = get_option(self::INTEGRATION_OPTION, []);

        return is_array($settings) ? trim((string) ($settings['api_key'] ?? '')) : '';
    }
}

(new Maruderm_KeyCRM_Status_Settings_Page(Maruderm_KeyCRM_Status_Config::instance()))->register();
