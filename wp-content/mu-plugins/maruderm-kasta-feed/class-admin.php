<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Admin
{
    public function __construct(private Settings $settings, private Generator $generator) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_maruderm_kasta_save', [$this, 'save']);
        add_action('admin_post_maruderm_kasta_generate', [$this, 'generate']);
    }

    public function menu(): void
    {
        add_submenu_page('woocommerce', 'Kasta XML', 'Kasta XML', 'manage_woocommerce', 'maruderm-kasta', [$this, 'render']);
    }

    private function authorize(string $action): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ! current_user_can('manage_woocommerce')) {
            wp_die('Доступ заборонено.', '', ['response' => 403]);
        }
        check_admin_referer($action);
    }

    public function save(): void
    {
        $this->authorize('maruderm_kasta_save');
        try {
            $this->settings->save(wp_unslash($_POST), ! empty($_POST['rotate_key']));
            $this->notice('Налаштування збережено.');
        } catch (\Throwable $error) { $this->notice($error->getMessage(), 'error'); }
        $this->redirect();
    }

    public function generate(): void
    {
        $this->authorize('maruderm_kasta_generate');
        try {
            $this->generator->queue();
            $this->notice('Формування XML заплановано. Оновіть сторінку, щоб перевірити результат.');
        } catch (\Throwable $error) { $this->notice($error->getMessage(), 'error'); }
        $this->redirect();
    }

    private function notice(string $message, string $type = 'success'): void
    {
        set_transient('maruderm_kasta_notice_' . get_current_user_id(), compact('message', 'type'), 120);
    }

    private function redirect(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=maruderm-kasta'));
        exit;
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) { wp_die('Доступ заборонено.', '', ['response' => 403]); }
        $settings = $this->settings->get();
        $status = get_option(Settings::STATUS, []);
        $status = is_array($status) ? $status : [];
        $notice = get_transient('maruderm_kasta_notice_' . get_current_user_id());
        delete_transient('maruderm_kasta_notice_' . get_current_user_id());
        $states = ['queued' => 'У черзі', 'running' => 'Формується', 'success' => 'Успішно', 'failed' => 'Помилка'];
        ?>
        <div class="wrap">
            <h1>Kasta XML</h1>
            <p>Українські назви та звичайні ціни сайту без знижок. Залишок: кількість у KeyCRM мінус резерв за всіма складами, за точним SKU.</p>
            <?php if (is_array($notice)) : ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?>"><p><?php echo esc_html($notice['message']); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maruderm_kasta_save">
                <?php wp_nonce_field('maruderm_kasta_save'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Доступ до фіда</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>> Увімкнути XML-фід</label><p class="description">Коли вимкнено, посилання повертає 404. Ручне формування залишається доступним.</p></td></tr>
                    <tr><th scope="row">Оновлення</th><td><label><input type="checkbox" name="automatic" value="1" <?php checked($settings['automatic']); ?>> Автоматично кожні 15 хвилин</label><p class="description">Працює лише для увімкненого фіда. Потрібна робота WordPress Cron або серверного планувальника.</p></td></tr>
                    <tr><th scope="row"><label for="kasta-age">Максимальний вік XML</label></th><td><input id="kasta-age" type="number" min="1" max="48" name="max_age_hours" value="<?php echo esc_attr((string) $settings['max_age_hours']); ?>"> годин<p class="description">Після цього строку фід повертає 503 до успішного оновлення. Це не обнуляє залишки товарів у Kasta.</p></td></tr>
                    <tr><th scope="row"><label for="kasta-url">URL XML для Kasta HUB</label></th><td><input id="kasta-url" type="url" readonly class="large-text code" value="<?php echo esc_attr($this->settings->url()); ?>"><p class="description">Збережіть налаштування, щоб створити URL. Доступний лише XML за цим посиланням. Каталог, звіти та інші файли недоступні.</p></td></tr>
                    <tr><th scope="row">Новий ключ доступу</th><td><label><input type="checkbox" name="rotate_key" value="1"> Змінити URL фіда</label><p class="description">Старе посилання перестане працювати. Новий URL потрібно вказати у Kasta HUB.</p></td></tr>
                </table>
                <?php submit_button('Зберегти налаштування'); ?>
            </form>
            <hr><h2>Ручне формування XML</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maruderm_kasta_generate">
                <?php wp_nonce_field('maruderm_kasta_generate'); submit_button('Сформувати XML зараз', 'secondary'); ?>
            </form>
            <p>Статус: <strong><?php echo esc_html($states[$status['state'] ?? ''] ?? 'Ще не запускався'); ?></strong>.</p>
            <?php if (! empty($status['last_success_at'])) : ?><p>Останній успішний запуск: <?php echo esc_html(wp_date('Y-m-d H:i:s', $status['last_success_at'])); ?></p><?php endif; ?>
            <?php if (! empty($status['error'])) : ?><div class="notice notice-error inline"><p><?php echo esc_html($status['error']); ?></p><p>Попередній XML збережено. Виправте помилку та повторіть запуск.</p></div><?php endif; ?>
            <?php if (in_array($status['state'] ?? '', ['queued', 'running'], true)) : ?><p>Оновіть сторінку через хвилину. Якщо статус не змінюється, перевірте роботу WordPress Cron і повторіть запуск.</p><?php endif; ?>
            <?php if (! empty($status['report'])) : $report = $status['report']; ?>
                <h2>Останній успішний звіт</h2>
                <p><?php echo esc_html(sprintf('Товарів: %d · Зіставлено SKU: %d · Зображень: %d · Доступних одиниць: %d · Пропущено: %d', $report['exported'], $report['matched_skus'], $report['images'], $report['stock_units'], count($report['excluded']))); ?></p>
                <?php if ($report['excluded'] !== []) : ?><details><summary>Пропущені SKU: немає ціни сайту та доступного залишку</summary><ul><?php foreach ($report['excluded'] as $item) : ?><li><?php echo esc_html($item['sku']); ?></li><?php endforeach; ?></ul></details><?php endif; ?>
                <?php if ($report['warnings'] !== []) : ?><details><summary>Попередження</summary><ul><?php foreach ($report['warnings'] as $warning) : ?><li><?php echo esc_html($warning); ?></li><?php endforeach; ?></ul></details><?php endif; ?>
            <?php endif; ?>
            <p>У Kasta HUB додайте URL фіда та перевірте зіставлення категорій і модерацію. Цей модуль формує XML; замовлення й залишки в KeyCRM він не змінює.</p>
        </div>
        <?php
    }
}
