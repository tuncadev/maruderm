<?php
/**
 * Central WooCommerce product price-control page.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Product_Pricing_Settings_Page
{
    public const PAGE_SLUG = 'maruderm-product-pricing';

    private const SAVE_ACTION = 'maruderm_save_product_pricing';
    private const CAPABILITY = 'manage_woocommerce';
    private const PER_PAGE = 50;

    private Maruderm_Product_Pricing_Policy $policy;
    private Maruderm_Product_Price_Repository $repository;
    private string $page_hook = '';

    public function __construct(
        Maruderm_Product_Pricing_Policy $policy,
        Maruderm_Product_Price_Repository $repository
    ) {
        $this->policy = $policy;
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_page'], 40);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_' . self::SAVE_ACTION, [$this, 'handle_save']);
    }

    public function register_page(): void
    {
        $this->page_hook = (string) add_submenu_page(
            'woocommerce',
            'Product Price Controls',
            'Price Controls',
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_product_editor = $screen && $screen->post_type === 'product' && in_array($screen->base, ['post', 'post-new'], true);
        if ($hook !== $this->page_hook && ! $is_product_editor) {
            return;
        }

        $css_path = dirname(__DIR__) . '/assets/maruderm-product-pricing-controls.css';
        $js_path = dirname(__DIR__) . '/assets/maruderm-product-pricing-controls.js';

        wp_enqueue_style(
            'maruderm-product-pricing-controls',
            content_url('/mu-plugins/assets/maruderm-product-pricing-controls.css'),
            [],
            is_file($css_path) ? (string) filemtime($css_path) : null
        );
        wp_enqueue_script(
            'maruderm-product-pricing-controls',
            content_url('/mu-plugins/assets/maruderm-product-pricing-controls.js'),
            [],
            is_file($js_path) ? (string) filemtime($js_path) : null,
            true
        );
        wp_localize_script('maruderm-product-pricing-controls', 'MarudermProductPricing', [
            'costLabel' => 'Cost price (private)',
            'invalidMessage' => 'Pricing was not saved. Required order: cost ≤ minimum ≤ sale < regular.',
        ]);
    }

    public function render_page(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage product pricing.', 'maruderm'));
        }

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $requested_page = isset($_GET['product_page']) ? absint($_GET['product_page']) : 1;
        $result = $this->repository->paginated_products($search, $requested_page, self::PER_PAGE);
        $notice = $this->pull_notice();
        ?>
        <div class="wrap mpp-page">
            <header class="mpp-hero">
                <div>
                    <p class="mpp-eyebrow"><span class="dashicons dashicons-lock" aria-hidden="true"></span> Private pricing</p>
                    <h1>Product price controls</h1>
                    <p>Manage acquisition cost and the lowest permitted sale price. These values are never shown to customers.</p>
                </div>
                <div class="mpp-rule" aria-label="Required price order">
                    <span>Required order</span>
                    <strong>Cost ≤ Minimum ≤ Sale &lt; Regular</strong>
                </div>
            </header>

            <?php $this->render_notice($notice); ?>

            <?php if (! $this->repository->cogs_enabled()) : ?>
                <div class="notice notice-error inline"><p><strong>WooCommerce Cost of Goods Sold is disabled.</strong> Enable it under WooCommerce → Settings → Advanced → Features before saving private prices.</p></div>
            <?php endif; ?>

            <form class="mpp-search" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                <label class="screen-reader-text" for="mpp-product-search">Search products</label>
                <input id="mpp-product-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, SKU, barcode, or ID">
                <button class="button" type="submit">Search products</button>
                <?php if ($search !== '') : ?>
                    <a class="button-link" href="<?php echo esc_url($this->page_url()); ?>">Clear</a>
                <?php endif; ?>
                <span class="mpp-result-count"><?php echo esc_html(sprintf('%d products', $result['total'])); ?></span>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-mpp-form>
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SAVE_ACTION); ?>">
                <input type="hidden" name="return_search" value="<?php echo esc_attr($search); ?>">
                <input type="hidden" name="return_page" value="<?php echo esc_attr((string) $result['page']); ?>">
                <?php wp_nonce_field(self::SAVE_ACTION); ?>

                <section class="mpp-card">
                    <div class="mpp-table-wrap">
                        <table class="widefat striped mpp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Regular price</th>
                                    <th scope="col">Sale price</th>
                                    <th scope="col">Cost price <span class="mpp-private">Private</span></th>
                                    <th scope="col">Minimum sale price <span class="mpp-private">Private</span></th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result['items'] === []) : ?>
                                    <tr><td colspan="6">No matching products.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($result['items'] as $product) : ?>
                                    <?php $this->render_product_row($product); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <footer class="mpp-card-footer">
                        <?php $this->render_pagination($result['page'], $result['pages'], $search); ?>
                        <button class="button button-primary" type="submit"<?php disabled(! $this->repository->cogs_enabled()); ?>>Save private prices</button>
                    </footer>
                </section>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage product pricing.', 'maruderm'));
        }
        check_admin_referer(self::SAVE_ACTION);

        if (! $this->repository->cogs_enabled()) {
            $this->redirect_with_notice('error', ['WooCommerce Cost of Goods Sold is disabled.']);
        }

        $submitted = isset($_POST['pricing']) && is_array($_POST['pricing'])
            ? wp_unslash($_POST['pricing'])
            : [];
        $updates = [];
        $messages = [];

        foreach ($submitted as $raw_product_id => $values) {
            $product_id = absint($raw_product_id);
            $product = wc_get_product($product_id);
            if (! $product instanceof WC_Product || ! is_array($values)) {
                $messages[] = sprintf('Product #%d could not be loaded.', $product_id);
                continue;
            }

            $cost = $this->policy->parse_money($values['cost'] ?? null, 'Cost price');
            $minimum = $this->policy->parse_money($values['minimum'] ?? null, 'Minimum sale price');
            if (is_wp_error($cost) || is_wp_error($minimum)) {
                $error = is_wp_error($cost) ? $cost : $minimum;
                $messages[] = sprintf('%s: %s', $product->get_name(), $this->policy->first_error_message($error));
                continue;
            }

            $errors = $this->policy->validate(
                $cost,
                $minimum,
                $this->nullable_float($product->get_regular_price('edit')),
                $this->nullable_float($product->get_sale_price('edit'))
            );
            if ($errors->has_errors()) {
                $messages[] = sprintf('%s: %s', $product->get_name(), $this->policy->first_error_message($errors));
                continue;
            }

            $updates[] = [$product, $cost, $minimum];
        }

        if ($messages !== []) {
            $this->redirect_with_notice('error', $messages);
        }

        try {
            foreach ($updates as [$product, $cost, $minimum]) {
                $this->repository->save_private_prices($product, $cost, $minimum);
            }
        } catch (Throwable $exception) {
            $this->redirect_with_notice('error', ['Private prices could not be saved: ' . $exception->getMessage()]);
        }

        $this->redirect_with_notice('success', [sprintf('Saved private prices for %d products.', count($updates))]);
    }

    private function render_product_row(WC_Product $product): void
    {
        $cost = $this->repository->cost($product);
        $minimum = $this->repository->minimum($product);
        $regular = $this->nullable_float($product->get_regular_price('edit'));
        $sale = $this->nullable_float($product->get_sale_price('edit'));
        $errors = $this->policy->validate($cost, $minimum, $regular, $sale);
        $configured = $cost !== null && $minimum !== null;
        $status_message = $errors->has_errors()
            ? $this->policy->first_error_message($errors)
            : ($configured ? 'Protected' : 'Not configured');
        $edit_url = get_edit_post_link($product->get_id(), 'raw');
        ?>
        <tr data-mpp-row>
            <td>
                <strong><a href="<?php echo esc_url((string) $edit_url); ?>"><?php echo esc_html($product->get_name()); ?></a></strong>
                <span class="mpp-product-meta">#<?php echo esc_html((string) $product->get_id()); ?> · SKU <?php echo esc_html($product->get_sku() ?: '—'); ?></span>
            </td>
            <td data-mpp-regular="<?php echo esc_attr($this->format_nullable($regular)); ?>"><?php echo esc_html($this->display_money($regular)); ?></td>
            <td data-mpp-sale="<?php echo esc_attr($this->format_nullable($sale)); ?>"><?php echo esc_html($this->display_money($sale)); ?></td>
            <td><input class="small-text" type="number" min="0" step="<?php echo esc_attr($this->price_step()); ?>" name="pricing[<?php echo esc_attr((string) $product->get_id()); ?>][cost]" value="<?php echo esc_attr($this->format_nullable($cost)); ?>" data-mpp-cost></td>
            <td><input class="small-text" type="number" min="0" step="<?php echo esc_attr($this->price_step()); ?>" name="pricing[<?php echo esc_attr((string) $product->get_id()); ?>][minimum]" value="<?php echo esc_attr($this->format_nullable($minimum)); ?>" data-mpp-minimum></td>
            <td><span class="mpp-status <?php echo $errors->has_errors() || ! $configured ? 'is-incomplete' : 'is-valid'; ?>"><?php echo esc_html($status_message); ?></span></td>
        </tr>
        <?php
    }

    private function render_pagination(int $page, int $pages, string $search): void
    {
        if ($pages <= 1) {
            echo '<span></span>';
            return;
        }

        echo '<nav class="mpp-pagination" aria-label="Product pages">';
        echo wp_kses_post(paginate_links([
            'base' => add_query_arg(['page' => self::PAGE_SLUG, 'product_page' => '%#%', 's' => $search], admin_url('admin.php')),
            'format' => '',
            'current' => $page,
            'total' => $pages,
            'type' => 'list',
        ]));
        echo '</nav>';
    }

    private function render_notice(?array $notice): void
    {
        if ($notice === null) {
            return;
        }

        $class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><ul>';
        foreach ($notice['messages'] as $message) {
            echo '<li>' . esc_html((string) $message) . '</li>';
        }
        echo '</ul></div>';
    }

    private function redirect_with_notice(string $type, array $messages): void
    {
        $key = $this->notice_key();
        set_transient($key, ['type' => $type, 'messages' => array_slice($messages, 0, 20)], MINUTE_IN_SECONDS);
        $search = isset($_POST['return_search']) ? sanitize_text_field(wp_unslash($_POST['return_search'])) : '';
        $page = isset($_POST['return_page']) ? max(1, absint($_POST['return_page'])) : 1;

        wp_safe_redirect(add_query_arg([
            'page' => self::PAGE_SLUG,
            'product_page' => $page,
            's' => $search,
            'pricing_notice' => '1',
        ], admin_url('admin.php')));
        exit;
    }

    private function pull_notice(): ?array
    {
        if (! isset($_GET['pricing_notice'])) {
            return null;
        }

        $key = $this->notice_key();
        $notice = get_transient($key);
        delete_transient($key);

        return is_array($notice) ? $notice : null;
    }

    private function notice_key(): string
    {
        return 'maruderm_product_pricing_notice_' . get_current_user_id();
    }

    private function page_url(): string
    {
        return add_query_arg('page', self::PAGE_SLUG, admin_url('admin.php'));
    }

    private function nullable_float($value): ?float
    {
        return $value === '' || $value === null ? null : (float) $value;
    }

    private function format_nullable(?float $value): string
    {
        return $value === null ? '' : wc_format_decimal($value, wc_get_price_decimals());
    }

    private function display_money(?float $value): string
    {
        return $value === null
            ? '—'
            : html_entity_decode(
                wp_strip_all_tags(wc_price($value)),
                ENT_QUOTES,
                (string) get_bloginfo('charset')
            );
    }

    private function price_step(): string
    {
        return wc_get_price_decimals() > 0 ? '0.' . str_repeat('0', wc_get_price_decimals() - 1) . '1' : '1';
    }
}
