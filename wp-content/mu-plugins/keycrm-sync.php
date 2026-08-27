<?php
/**
 * KeyCRM sync controls for WooCommerce products.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class KeyCRM_Sync_Config
{
    public const OPTION_KEY = 'keycrm_sync_options';

    private const DEFAULT_PRODUCTS_URL = 'https://openapi.keycrm.app/v1/products';
    private const DEFAULT_CATEGORIES_URL = 'https://openapi.keycrm.app/v1/products/categories';

    public function get_products_url(): string
    {
        return $this->get_option_url('products_url', self::DEFAULT_PRODUCTS_URL);
    }

    public function get_categories_url(): string
    {
        return $this->get_option_url('categories_url', self::DEFAULT_CATEGORIES_URL);
    }

    public function get_token(): string
    {
        $token = getenv('KEYCRM_API_TOKEN');

        if ((! is_string($token) || trim($token) === '') && defined('KEYCRM_API_TOKEN')) {
            $token = (string) constant('KEYCRM_API_TOKEN');
        }

        if (! is_string($token) || trim($token) === '') {
            $options = $this->get_options();
            $token = (string) ($options['api_token'] ?? '');
        }

        return is_string($token) ? trim($token) : '';
    }

    public function get_token_source(): string
    {
        $env_token = getenv('KEYCRM_API_TOKEN');

        if (is_string($env_token) && trim($env_token) !== '') {
            return 'environment';
        }

        if (defined('KEYCRM_API_TOKEN') && trim((string) constant('KEYCRM_API_TOKEN')) !== '') {
            return 'constant';
        }

        return $this->get_token() !== '' ? 'saved option' : 'missing';
    }

    public function get_options(): array
    {
        $options = get_option(self::OPTION_KEY, []);

        return is_array($options) ? $options : [];
    }

    public function sanitize_options(array $input): array
    {
        $current = $this->get_options();
        $token = trim((string) ($input['api_token'] ?? ''));

        return [
            'products_url' => esc_url_raw((string) ($input['products_url'] ?? self::DEFAULT_PRODUCTS_URL)),
            'categories_url' => esc_url_raw((string) ($input['categories_url'] ?? self::DEFAULT_CATEGORIES_URL)),
            'api_token' => $token !== '' ? $token : (string) ($current['api_token'] ?? ''),
        ];
    }

    private function get_option_url(string $key, string $default): string
    {
        $options = $this->get_options();
        $url = trim((string) ($options[$key] ?? ''));

        return $url !== '' ? $url : $default;
    }
}

final class KeyCRM_Sync_Reporter
{
    private array $messages = [];

    public function log(string $message): void
    {
        $this->messages[] = ['level' => 'log', 'message' => $message];

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
        }
    }

    public function warning(string $message): void
    {
        $this->messages[] = ['level' => 'warning', 'message' => $message];

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::warning($message);
        }
    }

    public function success(string $message): void
    {
        $this->messages[] = ['level' => 'success', 'message' => $message];

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::success($message);
        }
    }

    public function messages(): array
    {
        return $this->messages;
    }
}

final class KeyCRM_Sync_Service
{
    private const ENGLISH_NAME_META = '_keycrm_english_name';

    private KeyCRM_Sync_Config $config;
    private KeyCRM_Sync_Reporter $reporter;

    public function __construct(KeyCRM_Sync_Config $config, KeyCRM_Sync_Reporter $reporter)
    {
        $this->config = $config;
        $this->reporter = $reporter;
    }

    public function sync_categories(): array
    {
        $this->ensure_ready();

        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
        ]);

        if (is_wp_error($terms)) {
            throw new RuntimeException($terms->get_error_message());
        }

        $remote_categories = $this->remote_category_index();

        foreach ($terms as $term) {
            $stats['processed']++;
            $mapped_id = (int) get_term_meta($term->term_id, '_keycrm_id', true);
            $remote_name_key = $this->category_name_key($term->name);

            if ($mapped_id > 0 && ($remote_categories['ids'][$mapped_id] ?? '') === $remote_name_key) {
                $stats['skipped']++;
                $this->reporter->log("SKIP {$term->name} (already mapped remotely)");
                continue;
            }

            if ($mapped_id > 0 && isset($remote_categories['ids'][$mapped_id])) {
                $this->reporter->log("REMAP {$term->name} (mapped KeyCRM ID {$mapped_id} belongs to a different remote category)");
            } elseif ($mapped_id > 0) {
                $this->reporter->log("RECREATE {$term->name} (mapped KeyCRM ID {$mapped_id} is missing remotely)");
            }

            if (isset($remote_categories['names'][$remote_name_key])) {
                $remote_id = (int) $remote_categories['names'][$remote_name_key];
                update_term_meta($term->term_id, '_keycrm_id', $remote_id);
                $stats['skipped']++;
                $this->reporter->log("MAP {$term->name} => {$remote_id} (already exists remotely)");
                continue;
            }

            $response = $this->create_remote_category($term->name);

            if (is_wp_error($response)) {
                $stats['failed']++;
                $this->reporter->warning("ERROR {$term->term_id}: " . $response->get_error_message());
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $body = json_decode((string) wp_remote_retrieve_body($response), true);
            $remote_id = $this->extract_remote_id(is_array($body) ? $body : []);

            if ($code >= 200 && $code < 300 && $remote_id > 0) {
                update_term_meta($term->term_id, '_keycrm_id', $remote_id);
                $remote_categories['ids'][$remote_id] = $remote_name_key;
                $remote_categories['names'][$remote_name_key] = $remote_id;
                $stats['created']++;
                $this->reporter->log("OK {$term->name} => {$remote_id}");
                continue;
            }

            $stats['failed']++;
            $this->reporter->warning("FAIL {$term->name} => {$code}");
        }

        $this->reporter->success('Categories synced.');

        return $this->with_messages($stats);
    }

    private function remote_category_index(): array
    {
        $response = wp_remote_get($this->config->get_categories_url(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get_token(),
                'Accept' => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            throw new RuntimeException('KeyCRM categories lookup failed: ' . $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException("KeyCRM categories lookup failed with HTTP {$code}.");
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $items = is_array($body) ? (array) ($body['data'] ?? []) : [];
        $index = [
            'ids' => [],
            'names' => [],
        ];

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['id'])) {
                continue;
            }

            $id = (int) $item['id'];
            $name = trim((string) ($item['name'] ?? ''));

            if ($name !== '') {
                $name_key = $this->category_name_key($name);
                $index['ids'][$id] = $name_key;
                $index['names'][$name_key] = $id;
            }
        }

        return $index;
    }

    private function create_remote_category(string $name)
    {
        return wp_remote_post($this->config->get_categories_url(), [
            'headers' => $this->json_headers(),
            'body' => wp_json_encode([
                'name' => $name,
                'parent_id' => null,
            ]),
            'timeout' => 20,
        ]);
    }

    public function sync_products(int $limit = 0): array
    {
        $this->ensure_ready();

        if (! function_exists('wc_get_products')) {
            throw new RuntimeException('WooCommerce is not available.');
        }

        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $paged = 1;
        $per_page = 50;

        do {
            $products = wc_get_products([
                'limit' => $per_page,
                'paged' => $paged,
                'status' => ['publish'],
            ]);

            if (! $products) {
                break;
            }

            foreach ($products as $product) {
                if ($limit > 0 && $stats['processed'] >= $limit) {
                    $this->reporter->success('Products sync limit reached.');

                    return $this->with_messages($stats);
                }

                $stats['processed']++;
                $result = $this->sync_product($product);
                $stats[$result]++;
            }

            $paged++;
        } while (count($products) === $per_page);

        $this->reporter->success('Products sync done.');

        return $this->with_messages($stats);
    }

    private function sync_product(WC_Product $product): string
    {
        $category_id = $this->mapped_category_id($product);

        if ($category_id <= 0) {
            $this->reporter->warning("SKIP {$product->get_id()} - no mapped category");
            return 'skipped';
        }

        $price = (float) $product->get_price();
        if ($price <= 0) {
            $this->reporter->warning("SKIP {$product->get_id()} - invalid price");
            return 'skipped';
        }

        $sku = $product->get_sku() ?: 'product-' . $product->get_id();
        if ($this->product_exists_by_sku($sku)) {
            $this->reporter->log("SKIP {$product->get_id()} - SKU exists ({$sku})");
            return 'skipped';
        }

        $english_name = trim((string) $product->get_meta(self::ENGLISH_NAME_META, true, 'edit'));
        if ($english_name === '') {
            $this->reporter->warning(
                "SKIP {$product->get_id()} - missing verified English KeyCRM name ({$sku})"
            );
            return 'skipped';
        }

        $response = wp_remote_post($this->config->get_products_url(), [
            'headers' => $this->json_headers(),
            'body' => wp_json_encode([
                'name' => $english_name,
                'description' => $product->get_description(),
                'pictures' => $this->product_picture_urls($product),
                'currency_code' => get_woocommerce_currency(),
                'sku' => $sku,
                'price' => $price,
                'purchased_price' => $price,
                'unit_type' => get_post_meta($product->get_id(), 'unit_type', true) ?: 'шт',
                'weight' => (float) $product->get_weight(),
                'length' => (float) $product->get_length(),
                'width' => (float) $product->get_width(),
                'height' => (float) $product->get_height(),
                'category_id' => $category_id,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $this->reporter->warning("ERROR {$product->get_id()}: " . $response->get_error_message());
            return 'failed';
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            $this->reporter->log("OK {$product->get_id()}");
            return 'created';
        }

        $this->reporter->warning("FAIL {$product->get_id()} => {$code}");

        return 'failed';
    }

    private function product_exists_by_sku(string $sku): bool
    {
        $response = wp_remote_get($this->config->get_products_url() . '?search=' . rawurlencode($sku), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get_token(),
                'Accept' => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            $this->reporter->warning('SKU check failed: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        foreach ((array) ($body['data'] ?? []) as $item) {
            if (is_array($item) && (string) ($item['sku'] ?? '') === $sku) {
                return true;
            }
        }

        return false;
    }

    private function mapped_category_id(WC_Product $product): int
    {
        $terms = wp_get_post_terms($product->get_id(), 'product_cat');

        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        $term = $terms[0];
        $source_term_id = $term->parent ? (int) $term->parent : (int) $term->term_id;

        return (int) get_term_meta($source_term_id, '_keycrm_id', true);
    }

    private function product_picture_urls(WC_Product $product): array
    {
        $pictures = [];
        $thumbnail = get_the_post_thumbnail_url($product->get_id(), 'full');

        if ($thumbnail && $this->is_public_url($thumbnail)) {
            $pictures[] = $thumbnail;
        }

        foreach ($product->get_gallery_image_ids() as $gallery_id) {
            $url = wp_get_attachment_url($gallery_id);

            if ($url && $this->is_public_url($url)) {
                $pictures[] = $url;
            }
        }

        return $pictures;
    }

    private function json_headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get_token(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function ensure_ready(): void
    {
        if ($this->config->get_token() === '') {
            throw new RuntimeException('KEYCRM_API_TOKEN is not configured.');
        }
    }

    private function extract_remote_id(array $body): int
    {
        if (! empty($body['id'])) {
            return (int) $body['id'];
        }

        if (! empty($body['data']['id'])) {
            return (int) $body['data']['id'];
        }

        return 0;
    }

    private function category_name_key(string $name): string
    {
        $name = trim($name);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($name);
        }

        return strtolower($name);
    }

    private function is_public_url(string $url): bool
    {
        return ! str_contains($url, '.local') && ! str_contains($url, 'localhost');
    }

    private function with_messages(array $stats): array
    {
        $stats['messages'] = $this->reporter->messages();

        return $stats;
    }
}

final class KeyCRM_Sync_Admin
{
    private KeyCRM_Sync_Config $config;

    public function __construct(KeyCRM_Sync_Config $config)
    {
        $this->config = $config;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_keycrm_sync_run', [$this, 'handle_sync_action']);
    }

    public function register_menu(): void
    {
        add_options_page(
            'KeyCRM Sync',
            'KeyCRM Sync',
            'manage_options',
            'keycrm-sync',
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(KeyCRM_Sync_Config::OPTION_KEY, KeyCRM_Sync_Config::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this->config, 'sanitize_options'],
            'default' => [],
        ]);
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'settings_page_keycrm-sync') {
            return;
        }

        wp_register_style('keycrm-sync-admin', false, [], '1.0.0');
        wp_enqueue_style('keycrm-sync-admin');
        wp_add_inline_style('keycrm-sync-admin', $this->admin_css());
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $options = $this->config->get_options();
        $notice = get_transient($this->notice_key());
        delete_transient($this->notice_key());
        ?>
        <div class="wrap keycrm-sync-shell">
            <div class="keycrm-sync-hero">
                <div>
                    <p class="keycrm-sync-eyebrow"><?php esc_html_e('WooCommerce integration', 'maruderm'); ?></p>
                    <h1><?php esc_html_e('KeyCRM Sync', 'maruderm'); ?></h1>
                    <p><?php esc_html_e('Manage endpoints, credentials, and manual product/category syncs from one controlled panel.', 'maruderm'); ?></p>
                </div>
                <div class="keycrm-sync-token">
                    <span><?php esc_html_e('Token source', 'maruderm'); ?></span>
                    <strong><?php echo esc_html($this->config->get_token_source()); ?></strong>
                </div>
            </div>

            <?php if (is_array($notice)) : ?>
                <div class="keycrm-sync-notice keycrm-sync-notice-<?php echo esc_attr($notice['type'] ?? 'info'); ?>">
                    <div class="keycrm-sync-notice-summary">
                        <span class="keycrm-sync-status-dot"></span>
                        <p><?php echo esc_html($notice['message'] ?? ''); ?></p>
                    </div>
                    <?php $this->render_log_details((array) ($notice['details'] ?? [])); ?>
                </div>
            <?php endif; ?>

            <div class="keycrm-sync-grid">
                <section class="keycrm-sync-card keycrm-sync-settings-card">
                    <div class="keycrm-sync-card-head">
                        <h2><?php esc_html_e('Connection Settings', 'maruderm'); ?></h2>
                        <p><?php esc_html_e('Keep API URLs and credentials centralized for CLI and dashboard syncs.', 'maruderm'); ?></p>
                    </div>
                    <form method="post" action="options.php" class="keycrm-sync-form">
                        <?php settings_fields(KeyCRM_Sync_Config::OPTION_KEY); ?>

                        <div class="keycrm-sync-field">
                            <label for="keycrm-products-url"><?php esc_html_e('Products endpoint', 'maruderm'); ?></label>
                            <input id="keycrm-products-url" type="url" name="<?php echo esc_attr(KeyCRM_Sync_Config::OPTION_KEY); ?>[products_url]" value="<?php echo esc_attr($this->config->get_products_url()); ?>" />
                        </div>

                        <div class="keycrm-sync-field">
                            <label for="keycrm-categories-url"><?php esc_html_e('Categories endpoint', 'maruderm'); ?></label>
                            <input id="keycrm-categories-url" type="url" name="<?php echo esc_attr(KeyCRM_Sync_Config::OPTION_KEY); ?>[categories_url]" value="<?php echo esc_attr($this->config->get_categories_url()); ?>" />
                        </div>

                        <div class="keycrm-sync-field">
                            <label for="keycrm-api-token"><?php esc_html_e('API token', 'maruderm'); ?></label>
                            <input id="keycrm-api-token" type="password" name="<?php echo esc_attr(KeyCRM_Sync_Config::OPTION_KEY); ?>[api_token]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr($this->token_placeholder($options)); ?>" />
                            <p><?php echo esc_html(sprintf('Current token source: %s.', $this->config->get_token_source())); ?></p>
                        </div>

                        <div class="keycrm-sync-actions">
                            <?php submit_button(__('Save Settings', 'maruderm'), 'primary', 'submit', false); ?>
                        </div>
                    </form>
                </section>

                <section class="keycrm-sync-card">
                    <div class="keycrm-sync-card-head">
                        <h2><?php esc_html_e('Run Sync', 'maruderm'); ?></h2>
                        <p><?php esc_html_e('Trigger a controlled push from WooCommerce into the configured KeyCRM account.', 'maruderm'); ?></p>
                    </div>

                    <div class="keycrm-sync-run-list">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="keycrm-sync-run-card">
                            <?php wp_nonce_field('keycrm_sync_run'); ?>
                            <input type="hidden" name="action" value="keycrm_sync_run" />
                            <input type="hidden" name="sync_type" value="categories" />
                            <div>
                                <h3><?php esc_html_e('Categories', 'maruderm'); ?></h3>
                                <p><?php esc_html_e('Validate mappings and create missing top-level KeyCRM categories.', 'maruderm'); ?></p>
                            </div>
                            <?php submit_button(__('Sync Categories', 'maruderm'), 'secondary keycrm-sync-button', 'submit', false); ?>
                        </form>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="keycrm-sync-run-card">
                            <?php wp_nonce_field('keycrm_sync_run'); ?>
                            <input type="hidden" name="action" value="keycrm_sync_run" />
                            <input type="hidden" name="sync_type" value="products" />
                            <div>
                                <h3><?php esc_html_e('Products', 'maruderm'); ?></h3>
                                <p><?php esc_html_e('Create missing products by SKU using the mapped KeyCRM category IDs.', 'maruderm'); ?></p>
                            </div>
                            <div class="keycrm-sync-limit">
                                <label for="keycrm-product-limit"><?php esc_html_e('Limit', 'maruderm'); ?></label>
                                <input id="keycrm-product-limit" type="number" name="product_limit" value="10" min="0" step="1" />
                            </div>
                            <?php submit_button(__('Sync Products', 'maruderm'), 'primary keycrm-sync-button', 'submit', false); ?>
                        </form>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function handle_sync_action(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to run KeyCRM sync.', 'maruderm'));
        }

        check_admin_referer('keycrm_sync_run');

        $sync_type = sanitize_key((string) ($_POST['sync_type'] ?? ''));
        $reporter = new KeyCRM_Sync_Reporter();
        $service = new KeyCRM_Sync_Service($this->config, $reporter);

        try {
            if ($sync_type === 'categories') {
                $stats = $service->sync_categories();
            } elseif ($sync_type === 'products') {
                $limit = max(0, absint($_POST['product_limit'] ?? 0));
                $stats = $service->sync_products($limit);
            } else {
                throw new RuntimeException('Invalid sync action.');
            }

            $this->store_notice('success', $this->summary_message($sync_type, $stats), $this->message_details($stats));
        } catch (Throwable $exception) {
            $this->store_notice('error', $exception->getMessage(), $this->message_details(['messages' => $reporter->messages()]));
        }

        wp_safe_redirect(admin_url('options-general.php?page=keycrm-sync'));
        exit;
    }

    private function summary_message(string $sync_type, array $stats): string
    {
        return sprintf(
            '%s sync completed. Processed: %d. Created: %d. Skipped: %d. Failed: %d.',
            ucfirst($sync_type),
            (int) ($stats['processed'] ?? 0),
            (int) ($stats['created'] ?? 0),
            (int) ($stats['skipped'] ?? 0),
            (int) ($stats['failed'] ?? 0)
        );
    }

    private function message_details(array $stats): array
    {
        $details = [];

        foreach ((array) ($stats['messages'] ?? []) as $message) {
            if (! is_array($message)) {
                continue;
            }

            $details[] = [
                'level' => (string) ($message['level'] ?? 'log'),
                'message' => (string) ($message['message'] ?? ''),
            ];
        }

        return $details;
    }

    private function render_log_details(array $details): void
    {
        if (empty($details)) {
            return;
        }
        ?>
        <div class="keycrm-sync-log-panel">
            <div class="keycrm-sync-log-head">
                <strong><?php esc_html_e('Sync log', 'maruderm'); ?></strong>
                <span><?php echo esc_html(sprintf('%d entries', count($details))); ?></span>
            </div>
            <ol class="keycrm-sync-log-list">
                <?php foreach (array_slice($details, 0, 25) as $detail) : ?>
                    <?php $log = $this->normalize_log_detail($detail); ?>
                    <li class="keycrm-sync-log-row keycrm-sync-log-<?php echo esc_attr($log['level']); ?>">
                        <span class="keycrm-sync-log-badge"><?php echo esc_html(strtoupper($log['label'])); ?></span>
                        <span class="keycrm-sync-log-message"><?php echo esc_html($log['message']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
    }

    private function normalize_log_detail($detail): array
    {
        if (is_array($detail)) {
            $level = sanitize_html_class((string) ($detail['level'] ?? 'log'));

            return [
                'level' => $level !== '' ? $level : 'log',
                'label' => (string) ($detail['level'] ?? 'log'),
                'message' => (string) ($detail['message'] ?? ''),
            ];
        }

        $message = (string) $detail;
        $label = 'log';

        if (str_contains($message, ':')) {
            [$label, $message] = array_map('trim', explode(':', $message, 2));
        }

        $level = sanitize_html_class(strtolower($label));

        return [
            'level' => $level !== '' ? $level : 'log',
            'label' => $label !== '' ? $label : 'log',
            'message' => $message,
        ];
    }

    private function token_placeholder(array $options): string
    {
        return empty($options['api_token'])
            ? __('Enter token or configure KEYCRM_API_TOKEN', 'maruderm')
            : __('Saved token will be kept if this is empty', 'maruderm');
    }

    private function store_notice(string $type, string $message, array $details = []): void
    {
        set_transient($this->notice_key(), [
            'type' => $type,
            'message' => $message,
            'details' => $details,
        ], 60);
    }

    private function notice_key(): string
    {
        return 'keycrm_sync_notice_' . get_current_user_id();
    }

    private function admin_css(): string
    {
        return <<<'CSS'
.keycrm-sync-shell {
    max-width: 1180px;
}

.keycrm-sync-shell * {
    box-sizing: border-box;
}

.keycrm-sync-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin: 24px 0 18px;
    padding: 26px 30px;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%);
    box-shadow: 0 12px 30px rgba(37, 99, 235, 0.08);
}

.keycrm-sync-hero h1 {
    margin: 0;
    color: #0f172a;
    font-size: 30px;
    line-height: 1.2;
}

.keycrm-sync-hero p {
    max-width: 680px;
    margin: 8px 0 0;
    color: #475569;
    font-size: 14px;
}

.keycrm-sync-eyebrow {
    margin: 0 0 8px !important;
    color: #0369a1 !important;
    font-size: 12px !important;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
}

.keycrm-sync-token {
    min-width: 190px;
    padding: 14px 16px;
    border: 1px solid rgba(14, 165, 233, 0.32);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.72);
}

.keycrm-sync-token span {
    display: block;
    color: #64748b;
    font-size: 12px;
}

.keycrm-sync-token strong {
    display: block;
    margin-top: 4px;
    color: #075985;
    font-size: 15px;
}

.keycrm-sync-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
    gap: 18px;
}

.keycrm-sync-card,
.keycrm-sync-notice {
    border: 1px solid #dbeafe;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.keycrm-sync-card {
    padding: 22px;
}

.keycrm-sync-card-head {
    margin-bottom: 18px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.keycrm-sync-card-head h2,
.keycrm-sync-run-card h3 {
    margin: 0;
    color: #0f172a;
    font-size: 18px;
    line-height: 1.35;
}

.keycrm-sync-card-head p,
.keycrm-sync-run-card p,
.keycrm-sync-field p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 13px;
}

.keycrm-sync-form {
    display: grid;
    gap: 16px;
}

.keycrm-sync-field label,
.keycrm-sync-limit label {
    display: block;
    margin-bottom: 7px;
    color: #334155;
    font-weight: 600;
}

.keycrm-sync-field input,
.keycrm-sync-limit input {
    width: 100%;
    min-height: 42px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
}

.keycrm-sync-field input:focus,
.keycrm-sync-limit input:focus {
    border-color: #38bdf8;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.18);
}

.keycrm-sync-actions {
    padding-top: 2px;
}

.keycrm-sync-shell .button,
.keycrm-sync-shell .button-primary,
.keycrm-sync-shell .button-secondary {
    min-height: 38px;
    padding: 4px 16px;
    border-radius: 8px;
    font-weight: 600;
}

.keycrm-sync-shell .button-primary {
    border-color: #0284c7;
    background: #0284c7;
}

.keycrm-sync-shell .button-primary:hover,
.keycrm-sync-shell .button-primary:focus {
    border-color: #0369a1;
    background: #0369a1;
}

.keycrm-sync-shell .button-secondary {
    border-color: #bae6fd;
    color: #075985;
    background: #f0f9ff;
}

.keycrm-sync-run-list {
    display: grid;
    gap: 14px;
}

.keycrm-sync-run-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border: 1px solid #e0f2fe;
    border-radius: 8px;
    background: #f8fafc;
}

.keycrm-sync-limit {
    width: 92px;
}

.keycrm-sync-limit input {
    text-align: center;
}

.keycrm-sync-notice {
    margin: 0 0 18px;
    overflow: hidden;
}

.keycrm-sync-notice-summary {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border-left: 4px solid #38bdf8;
    background: #f0f9ff;
}

.keycrm-sync-notice-success .keycrm-sync-notice-summary {
    border-left-color: #0ea5e9;
}

.keycrm-sync-notice-error .keycrm-sync-notice-summary {
    border-left-color: #ef4444;
    background: #fff7f7;
}

.keycrm-sync-notice-summary p {
    margin: 0;
    color: #0f172a;
    font-weight: 600;
}

.keycrm-sync-status-dot {
    width: 10px;
    height: 10px;
    flex: 0 0 10px;
    border-radius: 50%;
    background: #0ea5e9;
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.14);
}

.keycrm-sync-notice-error .keycrm-sync-status-dot {
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
}

.keycrm-sync-log-panel {
    padding: 14px 16px 16px;
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
}

.keycrm-sync-log-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #334155;
}

.keycrm-sync-log-head span {
    color: #64748b;
    font-size: 12px;
}

.keycrm-sync-log-list {
    display: grid;
    gap: 8px;
    margin: 0;
}

.keycrm-sync-log-row {
    display: grid;
    grid-template-columns: 76px minmax(0, 1fr);
    align-items: start;
    gap: 10px;
    margin: 0;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}

.keycrm-sync-log-badge {
    display: inline-flex;
    justify-content: center;
    min-width: 64px;
    padding: 3px 8px;
    border-radius: 999px;
    color: #0369a1;
    background: #e0f2fe;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.45;
}

.keycrm-sync-log-warning .keycrm-sync-log-badge {
    color: #92400e;
    background: #fef3c7;
}

.keycrm-sync-log-success .keycrm-sync-log-badge {
    color: #047857;
    background: #d1fae5;
}

.keycrm-sync-log-error .keycrm-sync-log-badge {
    color: #b91c1c;
    background: #fee2e2;
}

.keycrm-sync-log-message {
    min-width: 0;
    color: #334155;
    overflow-wrap: anywhere;
}

@media (max-width: 960px) {
    .keycrm-sync-hero,
    .keycrm-sync-grid {
        display: block;
    }

    .keycrm-sync-token,
    .keycrm-sync-card + .keycrm-sync-card {
        margin-top: 18px;
    }
}

@media (max-width: 640px) {
    .keycrm-sync-hero,
    .keycrm-sync-card {
        padding: 18px;
    }

    .keycrm-sync-run-card,
    .keycrm-sync-log-row {
        grid-template-columns: 1fr;
    }

    .keycrm-sync-limit {
        width: 100%;
    }
}
CSS;
    }
}

final class KeyCRM_Sync_Command
{
    public function categories(array $args, array $assoc_args): void
    {
        $reporter = new KeyCRM_Sync_Reporter();
        $service = new KeyCRM_Sync_Service(new KeyCRM_Sync_Config(), $reporter);

        try {
            $service->sync_categories();
        } catch (Throwable $exception) {
            WP_CLI::error($exception->getMessage());
        }
    }

    public function products(array $args, array $assoc_args): void
    {
        $reporter = new KeyCRM_Sync_Reporter();
        $service = new KeyCRM_Sync_Service(new KeyCRM_Sync_Config(), $reporter);

        try {
            $service->sync_products(absint($assoc_args['limit'] ?? 0));
        } catch (Throwable $exception) {
            WP_CLI::error($exception->getMessage());
        }
    }
}

$keycrm_sync_config = new KeyCRM_Sync_Config();

if (is_admin()) {
    (new KeyCRM_Sync_Admin($keycrm_sync_config))->register();
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('keycrm', 'KeyCRM_Sync_Command');
}
