<?php

declare(strict_types=1);

namespace Maruderm\Account;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Removes parent-theme shells superseded by canonical account/auth layouts. */
final class AccountPage implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_action('wp', [$this, 'removeInheritedLayout'], 20);
    }

    public function removeInheritedLayout(): void
    {
        $isAccount = function_exists('is_account_page') && is_account_page();
        $isLogin = is_page('login') || is_page_template('template-login-register.php');

        if (!$isAccount && !$isLogin) {
            return;
        }

        remove_action('martfury_after_header', 'martfury_page_header');
        remove_action('martfury_after_site_content_open', 'martfury_open_site_content_container');
        remove_action('martfury_before_site_content_close', 'martfury_close_site_content_container');
    }
}
