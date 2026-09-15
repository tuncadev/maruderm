<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Generator
{
    public function __construct(private Settings $settings, private Storage $storage, private Source $source) {}

    public function queue(): void
    {
        if (wp_next_scheduled(Settings::MANUAL_HOOK)) { return; }
        $result = wp_schedule_single_event(time(), Settings::MANUAL_HOOK, [], true);
        if (is_wp_error($result) || ! $result) { throw new \RuntimeException('Не вдалося запланувати формування XML.'); }
        $status = get_option(Settings::STATUS, []);
        update_option(Settings::STATUS, array_merge(is_array($status) ? $status : [], ['state' => 'queued', 'queued_at' => time(), 'error' => '']), false);
        spawn_cron();
    }

    public function automatic(): void
    {
        $settings = $this->settings->get();
        if ($settings['enabled'] && $settings['automatic']) { $this->run(); }
    }

    public function cli(): void
    {
        $status = $this->run();
        if ($status['state'] !== 'success') {
            \WP_CLI::error($status['error'] ?? 'Generation already running.');
            return;
        }
        \WP_CLI::success(sprintf('Kasta XML: %d offers, %d available units.', $status['report']['exported'], $status['report']['stock_units']));
    }

    public function run(): array
    {
        $lock = false;
        $status = get_option(Settings::STATUS, []);
        $status = is_array($status) ? $status : [];
        try {
            $lock = fopen($this->storage->path('generation.lock', true), 'c');
            if ($lock === false) { throw new \RuntimeException('Cannot open generation lock.'); }
            if (! flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); return ['state' => 'running']; }
            chmod($this->storage->path('generation.lock'), 0600);
            $status = array_merge($status, ['state' => 'running', 'started_at' => time(), 'error' => '']);
            update_option(Settings::STATUS, $status, false);
            wp_raise_memory_limit('admin');
            if (is_callable('set_time_limit')) { set_time_limit(300); }
            $source = $this->source->collect();
            $mapper = new Catalog();
            $catalog = $mapper->build($source, $this->storage->read());
            $images = (new Media())->validate($catalog['offers']);
            if (time() - strtotime($source['generated_at_utc']) > 900) { throw new \RuntimeException('Stock snapshot expired during generation.'); }
            $xml = $mapper->xml($catalog);
            $this->storage->write($xml);
            $status = ['state' => 'success', 'started_at' => $status['started_at'], 'finished_at' => time(),
                'last_success_at' => time(), 'error' => '', 'report' => $catalog['report'] + ['images' => $images]];
        } catch (\Throwable $error) {
            $status = array_merge($status, ['state' => 'failed', 'finished_at' => time(), 'error' => $error->getMessage()]);
        } finally {
            if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
        }
        update_option(Settings::STATUS, $status, false);
        return $status;
    }
}
