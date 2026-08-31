<?php

declare(strict_types=1);

namespace Maruderm\Support;

if (!defined('ABSPATH')) {
    exit();
}

/** Reads the reviewed support-page content snapshot. */
final class SupportPageRepository
{
    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        $path = __DIR__ . '/support-pages.json';

        if (!is_readable($path)) {
            return null;
        }

        $pages = json_decode((string) file_get_contents($path), true);

        return is_array($pages) && is_array($pages[$slug] ?? null) ? $pages[$slug] : null;
    }
}
