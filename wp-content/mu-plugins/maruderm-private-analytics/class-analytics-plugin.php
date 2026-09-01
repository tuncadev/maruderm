<?php

namespace Maruderm\Analytics;

final class AnalyticsPlugin
{
    private const DATABASE_VERSION = '2';
    private const DATABASE_VERSION_OPTION = 'maruderm_analytics_db_version';
    private const CLEANUP_HOOK = 'maruderm_analytics_cleanup';

    public function boot(): void
    {
        global $wpdb;
        $repository = new AnalyticsRepository($wpdb);
        $restController = new AnalyticsRestController($repository);
        $adminPage = new AnalyticsAdminPage($repository);

        add_action('init', function () use ($repository): void {
            if (get_option(self::DATABASE_VERSION_OPTION) !== self::DATABASE_VERSION) {
                $repository->install();
                update_option(self::DATABASE_VERSION_OPTION, self::DATABASE_VERSION, false);
            }

            if (! wp_next_scheduled(self::CLEANUP_HOOK)) {
                wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK);
            }
        });
        add_action('rest_api_init', [$restController, 'registerRoutes']);
        add_action('admin_menu', [$adminPage, 'register']);
        add_action(self::CLEANUP_HOOK, static fn () => $repository->purgeExpired());
    }
}
