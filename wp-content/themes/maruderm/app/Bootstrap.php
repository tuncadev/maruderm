<?php

declare(strict_types=1);

namespace Maruderm;

use Maruderm\Kernel\Application;
use Maruderm\Kernel\Enqueue;

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/Kernel/Registrable.php';
require_once __DIR__ . '/Kernel/Application.php';
require_once __DIR__ . '/Kernel/Dependencies.php';
require_once __DIR__ . '/Kernel/Loadable.php';
require_once __DIR__ . '/Kernel/Helpers.php';
require_once __DIR__ . '/Kernel/Enqueue.php';

Enqueue::load();
Application::get_instance()->init();
