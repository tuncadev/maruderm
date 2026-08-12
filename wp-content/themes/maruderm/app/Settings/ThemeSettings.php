<?php

declare(strict_types=1);

namespace Maruderm\Settings;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

final class ThemeSettings implements Registrable
{
    use Loadable;

    public const PAGE_SLUG = 'maruderm-settings';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'Maruderm Settings',
            'Maruderm Settings',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage'],
            'dashicons-admin-customizer',
            58
        );

        add_submenu_page(
            self::PAGE_SLUG,
            'Maruderm Settings',
            'Maruderm Settings',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap"><h1>Maruderm Settings</h1></div>';
    }
}
