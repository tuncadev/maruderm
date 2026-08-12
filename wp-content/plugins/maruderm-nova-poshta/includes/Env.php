<?php

namespace MarudermNovaPoshta;

class Env
{
    /**
     * @var array<string, string>|null
     */
    private static ?array $cache = null;

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (is_array(self::$cache)) {
            return self::$cache;
        }

        $path = ABSPATH . '.env';
        $vars = [];

        if (is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                        continue;
                    }

                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    $value = trim($value, "\"'");

                    if ($key !== '') {
                        $vars[$key] = $value;
                    }
                }
            }
        }

        self::$cache = $vars;

        return $vars;
    }

    public static function get(string $key, string $default = ''): string
    {
        $env = getenv($key);
        if (is_string($env) && $env !== '') {
            return trim($env);
        }

        if (defined($key)) {
            $constant = constant($key);
            if (is_string($constant) && $constant !== '') {
                return trim($constant);
            }
        }

        $all = self::all();
        if (isset($all[$key]) && $all[$key] !== '') {
            return $all[$key];
        }

        return $default;
    }
}
