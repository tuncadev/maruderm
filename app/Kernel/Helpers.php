<?php

declare(strict_types=1);

namespace Maruderm\Kernel;

if (!defined('ABSPATH')) {
    exit();
}

final class Helpers
{
    public static function theme_path(string $path = ''): string
    {
        $base_path = trailingslashit(get_stylesheet_directory());

        return $path === ''
            ? untrailingslashit($base_path)
            : $base_path . ltrim($path, '/');
    }

    public static function theme_uri(string $path = ''): string
    {
        $base_uri = trailingslashit(get_stylesheet_directory_uri());

        return $path === ''
            ? untrailingslashit($base_uri)
            : $base_uri . ltrim($path, '/');
    }

    public static function dist_path(string $path = ''): string
    {
        $dist_path = 'dist';

        if ($path !== '') {
            $dist_path .= '/' . ltrim($path, '/');
        }

        return self::theme_path($dist_path);
    }

    public static function dist_uri(string $path = ''): string
    {
        $dist_path = 'dist';

        if ($path !== '') {
            $dist_path .= '/' . ltrim($path, '/');
        }

        return self::theme_uri($dist_path);
    }

    public static function vite_hot_path(): string
    {
        return self::theme_path('vite.hot');
    }
}
