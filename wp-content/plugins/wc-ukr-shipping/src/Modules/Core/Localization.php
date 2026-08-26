<?php

namespace kirillbdev\WCUkrShipping\Modules\Core;

use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class Localization implements ModuleInterface
{
    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_action('plugins_loaded', [ $this, 'loadPluginTextDomain' ]);
    }

    public function loadPluginTextDomain()
    {
        $this->migrateLocalization();

        load_plugin_textdomain(WCUS_TRANSLATE_DOMAIN, false, 'wc-ukr-shipping/lang');
    }

    private function migrateLocalization(): void
    {
        $migrated = get_option('wcus_i18n_migrated', null);
        if ($migrated === 'yes') {
            return;
        }

        $translateBase = 'wc-ukr-shipping';
        $locales = [
            'ru_RU',
            'uk',
        ];

        foreach ($locales as $locale) {
            $this->removeTranslateFile("$translateBase-$locale.po");
            $this->removeTranslateFile("$translateBase-$locale.mo");
            $this->removeTranslateFile("$translateBase-$locale.l10n.php");
        }

        update_option('wcus_i18n_migrated', 'yes');
    }

    private function removeTranslateFile(string $fileName): void
    {
        if (file_exists(WP_LANG_DIR . '/plugins/' . $fileName)) {
            @unlink(WP_LANG_DIR . '/plugins/' . $fileName);
        }
    }
}
