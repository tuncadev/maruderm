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

        foreach ($terms as $term) {
            $stats['processed']++;

            if (get_term_meta($term->term_id, '_keycrm_id', true)) {
                $stats['skipped']++;
                $this->reporter->log("SKIP {$term->name} (already mapped)");
                continue;
            }

            $response = wp_remote_post($this->config->get_categories_url(), [
                'headers' => $this->json_headers(),
                'body' => wp_json_encode([
                    'name' => $term->name,
                    'parent_id' => null,
                ]),
                'timeout' => 20,
            ]);

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

        $response = wp_remote_post($this->config->get_products_url(), [
            'headers' => $this->json_headers(),
            'body' => wp_json_encode([
                'name' => $product->get_name(),
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

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $options = $this->config->get_options();
        $notice = get_transient($this->notice_key());
        delete_transient($this->notice_key());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('KeyCRM Sync', 'maruderm'); ?></h1>

            <?php if (is_array($notice)) : ?>
                <div class="notice notice-<?php echo esc_attr($notice['type'] ?? 'info'); ?> is-dismissible">
                    <p><?php echo esc_html($notice['message'] ?? ''); ?></p>
                    <?php if (! empty($notice['details']) && is_array($notice['details'])) : ?>
                        <ul>
                            <?php foreach (array_slice($notice['details'], 0, 25) as $detail) : ?>
                                <li><?php echo esc_html((string) $detail); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields(KeyCRM_Sync_Config::OPTION_KEY); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="keycrm-products-url"><?php esc_html_e('Products endpoint', 'maruderm'); ?></label></th>
                        <td>
                            <input id="keycrm-products-url" class="regular-text" type="url" name="<?php echo esc_attr(KeyCRM_Sync_Config::OPTION_KEY); ?>[products_url]" value="<?php echo esc_attr($this->config->get_products_url()); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="keycrm-categories-url"><?php esc_html_e('Categories endpoint', 'maruderm'); ?></label></th>
                        <td>
                            <input id="keycrm-categories-url" class="regular-text" type="url" name="<?php echo esc_attr(KeyCRM_Sync_Config::OPTION_KEY); ?>[categories_url]" value="<?php echo esc_attr($this->config->get_categories_url()); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="keycrm-api-token"><?php esc_html_e('API token', 'maruderm'); ?></label></th>
                        <td>
                            <input id="keycrm-api-token" class="regular-text" type="password" name="<?php echo esc_attr(KeyCRM_Sync_Config::OPTION_KEY); ?>[api_token]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr($this->token_placeholder($options)); ?>" />
                            <p class="description"><?php echo esc_html(sprintf('Current token source: %s.', $this->config->get_token_source())); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'maruderm')); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Run Sync', 'maruderm'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:12px;">
                <?php wp_nonce_field('keycrm_sync_run'); ?>
                <input type="hidden" name="action" value="keycrm_sync_run" />
                <input type="hidden" name="sync_type" value="categories" />
                <?php submit_button(__('Sync Categories', 'maruderm'), 'secondary', 'submit', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                <?php wp_nonce_field('keycrm_sync_run'); ?>
                <input type="hidden" name="action" value="keycrm_sync_run" />
                <input type="hidden" name="sync_type" value="products" />
                <label for="keycrm-product-limit" style="margin-right:8px;"><?php esc_html_e('Product limit', 'maruderm'); ?></label>
                <input id="keycrm-product-limit" type="number" name="product_limit" value="10" min="0" step="1" style="width:90px;" />
                <?php submit_button(__('Sync Products', 'maruderm'), 'primary', 'submit', false); ?>
            </form>
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

            $details[] = strtoupper((string) ($message['level'] ?? 'log')) . ': ' . (string) ($message['message'] ?? '');
        }

        return $details;
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
