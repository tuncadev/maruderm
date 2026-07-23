<?php
/**
 * Admin-controlled database backups for Maruderm.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_DB_Backup_Config
{
    public const MENU_SLUG = 'maruderm-db-backups';
    public const NOTICE_KEY = 'maruderm_db_backup_notice_';

    public function backup_dir(): string
    {
        $outside_web_root = trailingslashit(dirname(ABSPATH)) . 'db-backups';

        if ($this->can_use_directory($outside_web_root)) {
            return $outside_web_root;
        }

        return trailingslashit(WP_CONTENT_DIR) . 'db-backups';
    }

    public function is_backup_dir_public(): bool
    {
        return str_starts_with(wp_normalize_path($this->backup_dir()), wp_normalize_path(WP_CONTENT_DIR));
    }

    private function can_use_directory(string $directory): bool
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        return is_writable(dirname($directory));
    }
}

final class Maruderm_DB_Backup_File
{
    private string $name;
    private string $path;
    private int $size;
    private int $modified_at;

    public function __construct(string $name, string $path, int $size, int $modified_at)
    {
        $this->name = $name;
        $this->path = $path;
        $this->size = $size;
        $this->modified_at = $modified_at;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function modified_at(): int
    {
        return $this->modified_at;
    }
}

final class Maruderm_DB_Backup_Service
{
    private Maruderm_DB_Backup_Config $config;

    public function __construct(Maruderm_DB_Backup_Config $config)
    {
        $this->config = $config;
    }

    public function create_backup(): Maruderm_DB_Backup_File
    {
        $this->ensure_ready();
        $directory = $this->ensure_backup_dir();
        $filename = sprintf('maruderm-db-%s.sql', gmdate('Ymd-His'));
        $path = trailingslashit($directory) . $filename;
        $temporary_path = $path . '.tmp';

        $command = $this->dump_command();
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $temporary_path, 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, ABSPATH, $this->process_environment());

        if (! is_resource($process)) {
            throw new RuntimeException('Could not start database export process.');
        }

        fclose($pipes[0]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exit_code = proc_close($process);

        if ($exit_code !== 0) {
            $this->delete_if_exists($temporary_path);
            throw new RuntimeException($this->export_error_message($error, $exit_code));
        }

        if (! rename($temporary_path, $path)) {
            $this->delete_if_exists($temporary_path);
            throw new RuntimeException('Database export completed but the backup file could not be finalized.');
        }

        return $this->backup_file_from_path($path);
    }

    /**
     * @return Maruderm_DB_Backup_File[]
     */
    public function backups(): array
    {
        $directory = $this->ensure_backup_dir();
        $paths = glob(trailingslashit($directory) . 'maruderm-db-*.sql') ?: [];
        $files = [];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $files[] = $this->backup_file_from_path($path);
        }

        usort($files, static fn (Maruderm_DB_Backup_File $left, Maruderm_DB_Backup_File $right): int => $right->modified_at() <=> $left->modified_at());

        return $files;
    }

    public function backup_file(string $filename): Maruderm_DB_Backup_File
    {
        $path = $this->backup_path($filename);

        if (! is_file($path)) {
            throw new RuntimeException('Backup file was not found.');
        }

        return $this->backup_file_from_path($path);
    }

    public function delete_backup(string $filename): void
    {
        $path = $this->backup_path($filename);

        if (! is_file($path)) {
            throw new RuntimeException('Backup file was not found.');
        }

        if (! unlink($path)) {
            throw new RuntimeException('Backup file could not be deleted.');
        }
    }

    public function backup_dir(): string
    {
        return $this->ensure_backup_dir();
    }

    private function ensure_ready(): void
    {
        if (! defined('DB_NAME') || ! defined('DB_USER') || ! defined('DB_PASSWORD') || ! defined('DB_HOST')) {
            throw new RuntimeException('WordPress database constants are not configured.');
        }

        if (! function_exists('proc_open')) {
            throw new RuntimeException('proc_open is disabled, so database export cannot run from the dashboard.');
        }

        if ($this->dump_binary() === '') {
            throw new RuntimeException('mysqldump or mariadb-dump was not found on the server.');
        }
    }

    private function ensure_backup_dir(): string
    {
        $directory = $this->config->backup_dir();

        if (! is_dir($directory) && ! wp_mkdir_p($directory)) {
            throw new RuntimeException('Backup directory could not be created.');
        }

        if (! is_writable($directory)) {
            throw new RuntimeException('Backup directory is not writable.');
        }

        $this->write_protection_files($directory);

        return $directory;
    }

    private function backup_path(string $filename): string
    {
        $filename = basename($filename);

        if (! preg_match('/^maruderm-db-\d{8}-\d{6}\.sql$/', $filename)) {
            throw new RuntimeException('Invalid backup file name.');
        }

        $directory = realpath($this->ensure_backup_dir());
        $path = realpath(trailingslashit((string) $directory) . $filename);

        if (! $directory || ! $path || ! str_starts_with(wp_normalize_path($path), trailingslashit(wp_normalize_path($directory)))) {
            throw new RuntimeException('Invalid backup path.');
        }

        return $path;
    }

    private function backup_file_from_path(string $path): Maruderm_DB_Backup_File
    {
        return new Maruderm_DB_Backup_File(
            basename($path),
            $path,
            (int) filesize($path),
            (int) filemtime($path)
        );
    }

    private function write_protection_files(string $directory): void
    {
        $htaccess = trailingslashit($directory) . '.htaccess';
        $index = trailingslashit($directory) . 'index.php';

        if (! file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        if (! file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
    }

    private function dump_command(): array
    {
        $host = (string) DB_HOST;
        $port = '';
        $socket = '';

        if (str_contains($host, ':')) {
            [$host_part, $suffix] = explode(':', $host, 2);

            if (ctype_digit($suffix)) {
                $host = $host_part;
                $port = $suffix;
            } elseif (str_starts_with($suffix, '/')) {
                $host = $host_part;
                $socket = $suffix;
            }
        }

        $command = [
            $this->dump_binary(),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            '--default-character-set=' . (defined('DB_CHARSET') && DB_CHARSET ? DB_CHARSET : 'utf8mb4'),
            '--host=' . $host,
            '--user=' . (string) DB_USER,
        ];

        if ($port !== '') {
            $command[] = '--port=' . $port;
        }

        if ($socket !== '') {
            $command[] = '--socket=' . $socket;
        }

        $command[] = (string) DB_NAME;

        return $command;
    }

    private function dump_binary(): string
    {
        foreach (['/usr/bin/mysqldump', '/usr/bin/mariadb-dump', '/usr/local/bin/mysqldump', '/usr/local/bin/mariadb-dump'] as $binary) {
            if (is_executable($binary)) {
                return $binary;
            }
        }

        return '';
    }

    private function process_environment(): array
    {
        $environment = [];

        foreach ($_ENV as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $environment[$key] = (string) $value;
            }
        }

        $environment['MYSQL_PWD'] = (string) DB_PASSWORD;

        return $environment;
    }

    private function export_error_message(string $error, int $exit_code): string
    {
        $message = trim($error) !== '' ? trim($error) : 'No error output was returned.';

        return sprintf('Database export failed with exit code %d: %s', $exit_code, $message);
    }

    private function delete_if_exists(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

final class Maruderm_DB_Backup_Admin
{
    private Maruderm_DB_Backup_Config $config;
    private Maruderm_DB_Backup_Service $service;

    public function __construct(Maruderm_DB_Backup_Config $config, Maruderm_DB_Backup_Service $service)
    {
        $this->config = $config;
        $this->service = $service;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_maruderm_db_backup_create', [$this, 'handle_create']);
        add_action('admin_post_maruderm_db_backup_download', [$this, 'handle_download']);
        add_action('admin_post_maruderm_db_backup_delete', [$this, 'handle_delete']);
    }

    public function register_menu(): void
    {
        add_management_page(
            'Database Backups',
            'Database Backups',
            'manage_options',
            Maruderm_DB_Backup_Config::MENU_SLUG,
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'tools_page_' . Maruderm_DB_Backup_Config::MENU_SLUG) {
            return;
        }

        wp_register_style('maruderm-db-backups-admin', false, [], '1.0.0');
        wp_enqueue_style('maruderm-db-backups-admin');
        wp_add_inline_style('maruderm-db-backups-admin', $this->admin_css());
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $notice = get_transient($this->notice_key());
        delete_transient($this->notice_key());
        $backups = $this->service->backups();
        ?>
        <div class="wrap maruderm-db-backup-shell">
            <div class="maruderm-db-backup-hero">
                <div>
                    <p class="maruderm-db-backup-eyebrow"><?php esc_html_e('Database protection', 'maruderm'); ?></p>
                    <h1><?php esc_html_e('Database Backups', 'maruderm'); ?></h1>
                    <p><?php esc_html_e('Create an on-demand SQL backup before risky imports, syncs, or content changes.', 'maruderm'); ?></p>
                </div>
                <div class="maruderm-db-backup-db">
                    <span><?php esc_html_e('Current database', 'maruderm'); ?></span>
                    <strong><?php echo esc_html((string) DB_NAME); ?></strong>
                    <small><?php echo esc_html((string) DB_HOST); ?></small>
                </div>
            </div>

            <?php if (is_array($notice)) : ?>
                <div class="maruderm-db-backup-notice maruderm-db-backup-notice-<?php echo esc_attr($notice['type'] ?? 'info'); ?>">
                    <span></span>
                    <p><?php echo esc_html($notice['message'] ?? ''); ?></p>
                </div>
            <?php endif; ?>

            <div class="maruderm-db-backup-grid">
                <section class="maruderm-db-backup-card">
                    <div class="maruderm-db-backup-card-head">
                        <h2><?php esc_html_e('Create Backup', 'maruderm'); ?></h2>
                        <p><?php esc_html_e('Exports the configured WordPress database to a timestamped SQL file.', 'maruderm'); ?></p>
                    </div>
                    <dl class="maruderm-db-backup-meta">
                        <div>
                            <dt><?php esc_html_e('Storage path', 'maruderm'); ?></dt>
                            <dd><?php echo esc_html($this->service->backup_dir()); ?></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e('Access', 'maruderm'); ?></dt>
                            <dd><?php echo esc_html($this->config->is_backup_dir_public() ? __('Protected with server files, but inside wp-content', 'maruderm') : __('Outside WordPress web root', 'maruderm')); ?></dd>
                        </div>
                    </dl>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('maruderm_db_backup_create'); ?>
                        <input type="hidden" name="action" value="maruderm_db_backup_create" />
                        <?php submit_button(__('Create DB Backup', 'maruderm'), 'primary maruderm-db-backup-button', 'submit', false); ?>
                    </form>
                </section>

                <section class="maruderm-db-backup-card maruderm-db-backup-list-card">
                    <div class="maruderm-db-backup-card-head">
                        <h2><?php esc_html_e('Backup Files', 'maruderm'); ?></h2>
                        <p><?php echo esc_html(sprintf(_n('%d backup available.', '%d backups available.', count($backups), 'maruderm'), count($backups))); ?></p>
                    </div>

                    <?php if (empty($backups)) : ?>
                        <div class="maruderm-db-backup-empty">
                            <?php esc_html_e('No database backups have been created yet.', 'maruderm'); ?>
                        </div>
                    <?php else : ?>
                        <div class="maruderm-db-backup-list">
                            <?php foreach ($backups as $backup) : ?>
                                <div class="maruderm-db-backup-row">
                                    <div>
                                        <strong><?php echo esc_html($backup->name()); ?></strong>
                                        <span><?php echo esc_html($this->format_size($backup->size())); ?> · <?php echo esc_html(wp_date('Y-m-d H:i:s', $backup->modified_at())); ?></span>
                                    </div>
                                    <div class="maruderm-db-backup-row-actions">
                                        <a class="button button-secondary" href="<?php echo esc_url($this->action_url('maruderm_db_backup_download', $backup)); ?>"><?php esc_html_e('Download', 'maruderm'); ?></a>
                                        <a class="button maruderm-db-backup-delete" href="<?php echo esc_url($this->action_url('maruderm_db_backup_delete', $backup)); ?>"><?php esc_html_e('Delete', 'maruderm'); ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
        <?php
    }

    public function handle_create(): void
    {
        $this->authorize_action('maruderm_db_backup_create');

        try {
            $backup = $this->service->create_backup();
            $this->store_notice('success', sprintf('Backup created: %s (%s).', $backup->name(), $this->format_size($backup->size())));
        } catch (Throwable $exception) {
            $this->store_notice('error', $exception->getMessage());
        }

        $this->redirect_to_page();
    }

    public function handle_download(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to download database backups.', 'maruderm'));
        }

        $filename = sanitize_file_name((string) ($_GET['file'] ?? ''));
        check_admin_referer('maruderm_db_backup_file_' . $filename);
        $backup = $this->service->backup_file($filename);

        nocache_headers();
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $backup->name() . '"');
        header('Content-Length: ' . $backup->size());
        readfile($backup->path());
        exit;
    }

    public function handle_delete(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to delete database backups.', 'maruderm'));
        }

        $filename = sanitize_file_name((string) ($_GET['file'] ?? ''));
        check_admin_referer('maruderm_db_backup_file_' . $filename);

        try {
            $this->service->delete_backup($filename);
            $this->store_notice('success', sprintf('Backup deleted: %s.', $filename));
        } catch (Throwable $exception) {
            $this->store_notice('error', $exception->getMessage());
        }

        $this->redirect_to_page();
    }

    private function authorize_action(string $nonce_action): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage database backups.', 'maruderm'));
        }

        check_admin_referer($nonce_action);
    }

    private function action_url(string $action, Maruderm_DB_Backup_File $backup): string
    {
        return wp_nonce_url(
            add_query_arg([
                'action' => $action,
                'file' => $backup->name(),
            ], admin_url('admin-post.php')),
            'maruderm_db_backup_file_' . $backup->name()
        );
    }

    private function format_size(int $bytes): string
    {
        return size_format($bytes, 2);
    }

    private function store_notice(string $type, string $message): void
    {
        set_transient($this->notice_key(), [
            'type' => $type,
            'message' => $message,
        ], 60);
    }

    private function redirect_to_page(): void
    {
        wp_safe_redirect(admin_url('tools.php?page=' . Maruderm_DB_Backup_Config::MENU_SLUG));
        exit;
    }

    private function notice_key(): string
    {
        return Maruderm_DB_Backup_Config::NOTICE_KEY . get_current_user_id();
    }

    private function admin_css(): string
    {
        return <<<'CSS'
.maruderm-db-backup-shell {
    max-width: 1180px;
}

.maruderm-db-backup-shell * {
    box-sizing: border-box;
}

.maruderm-db-backup-hero {
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

.maruderm-db-backup-hero h1 {
    margin: 0;
    color: #0f172a;
    font-size: 30px;
    line-height: 1.2;
}

.maruderm-db-backup-hero p {
    max-width: 660px;
    margin: 8px 0 0;
    color: #475569;
    font-size: 14px;
}

.maruderm-db-backup-eyebrow {
    margin: 0 0 8px !important;
    color: #0369a1 !important;
    font-size: 12px !important;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
}

.maruderm-db-backup-db {
    min-width: 220px;
    padding: 14px 16px;
    border: 1px solid rgba(14, 165, 233, 0.32);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.72);
}

.maruderm-db-backup-db span,
.maruderm-db-backup-db small {
    display: block;
    color: #64748b;
    font-size: 12px;
}

.maruderm-db-backup-db strong {
    display: block;
    margin-top: 4px;
    color: #075985;
    font-size: 15px;
}

.maruderm-db-backup-grid {
    display: grid;
    grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.2fr);
    gap: 18px;
}

.maruderm-db-backup-card,
.maruderm-db-backup-notice {
    border: 1px solid #dbeafe;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.maruderm-db-backup-card {
    padding: 22px;
}

.maruderm-db-backup-card-head {
    margin-bottom: 18px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.maruderm-db-backup-card-head h2 {
    margin: 0;
    color: #0f172a;
    font-size: 18px;
    line-height: 1.35;
}

.maruderm-db-backup-card-head p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 13px;
}

.maruderm-db-backup-meta {
    display: grid;
    gap: 12px;
    margin: 0 0 18px;
}

.maruderm-db-backup-meta div {
    padding: 12px;
    border: 1px solid #e0f2fe;
    border-radius: 8px;
    background: #f8fafc;
}

.maruderm-db-backup-meta dt {
    margin-bottom: 4px;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.maruderm-db-backup-meta dd {
    margin: 0;
    color: #0f172a;
    overflow-wrap: anywhere;
}

.maruderm-db-backup-shell .button,
.maruderm-db-backup-shell .button-primary,
.maruderm-db-backup-shell .button-secondary {
    min-height: 38px;
    padding: 4px 16px;
    border-radius: 8px;
    font-weight: 600;
}

.maruderm-db-backup-shell .button-primary {
    border-color: #0284c7;
    background: #0284c7;
}

.maruderm-db-backup-shell .button-primary:hover,
.maruderm-db-backup-shell .button-primary:focus {
    border-color: #0369a1;
    background: #0369a1;
}

.maruderm-db-backup-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 18px;
    padding: 14px 16px;
    border-left: 4px solid #0ea5e9;
    background: #f0f9ff;
}

.maruderm-db-backup-notice-error {
    border-left-color: #ef4444;
    background: #fff7f7;
}

.maruderm-db-backup-notice span {
    width: 10px;
    height: 10px;
    flex: 0 0 10px;
    border-radius: 50%;
    background: #0ea5e9;
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.14);
}

.maruderm-db-backup-notice-error span {
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
}

.maruderm-db-backup-notice p {
    margin: 0;
    color: #0f172a;
    font-weight: 600;
}

.maruderm-db-backup-empty {
    padding: 20px;
    border: 1px dashed #bae6fd;
    border-radius: 8px;
    color: #64748b;
    background: #f8fafc;
    text-align: center;
}

.maruderm-db-backup-list {
    display: grid;
    gap: 10px;
}

.maruderm-db-backup-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border: 1px solid #e0f2fe;
    border-radius: 8px;
    background: #f8fafc;
}

.maruderm-db-backup-row strong,
.maruderm-db-backup-row span {
    display: block;
}

.maruderm-db-backup-row strong {
    color: #0f172a;
    overflow-wrap: anywhere;
}

.maruderm-db-backup-row span {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}

.maruderm-db-backup-row-actions {
    display: flex;
    gap: 8px;
}

.maruderm-db-backup-delete {
    border-color: #fecaca !important;
    color: #b91c1c !important;
    background: #fff7f7 !important;
}

@media (max-width: 960px) {
    .maruderm-db-backup-hero,
    .maruderm-db-backup-grid {
        display: block;
    }

    .maruderm-db-backup-db,
    .maruderm-db-backup-card + .maruderm-db-backup-card {
        margin-top: 18px;
    }
}

@media (max-width: 640px) {
    .maruderm-db-backup-hero,
    .maruderm-db-backup-card {
        padding: 18px;
    }

    .maruderm-db-backup-row {
        grid-template-columns: 1fr;
    }

    .maruderm-db-backup-row-actions {
        flex-wrap: wrap;
    }
}
CSS;
    }
}

$maruderm_db_backup_config = new Maruderm_DB_Backup_Config();
$maruderm_db_backup_service = new Maruderm_DB_Backup_Service($maruderm_db_backup_config);

if (is_admin()) {
    (new Maruderm_DB_Backup_Admin($maruderm_db_backup_config, $maruderm_db_backup_service))->register();
}
