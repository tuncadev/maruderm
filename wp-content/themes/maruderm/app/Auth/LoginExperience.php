<?php

declare(strict_types=1);

namespace Maruderm\Auth;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Owns the global logged-out login drawer rendered directly after the header. */
final class LoginExperience implements Registrable
{
    use Loadable;

    public function register(): void
    {
        add_action('martfury_after_header', [$this, 'render'], 1);
    }

    public function render(): void
    {
        if (is_user_logged_in() || is_page_template('template-coming-soon-page.php')) {
            return;
        }

        (new LoginRenderer())->renderModal();
    }
}
