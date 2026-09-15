<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Storage
{
    public function __construct(private ?string $base = null) {}

    public function directory(bool $create = false): string
    {
        $wpRoot = realpath(ABSPATH);
        if ($wpRoot === false) { throw new \RuntimeException('Cannot resolve WordPress root.'); }
        $base = $this->base ?? (defined('MARUDERM_KASTA_STORAGE_DIR') ? (string) MARUDERM_KASTA_STORAGE_DIR : dirname($wpRoot) . '/.maruderm-private');
        if (! str_starts_with($base, '/') || str_contains($base, '..')) {
            throw new \RuntimeException('Kasta storage must be an absolute private path.');
        }
        $path = rtrim($base, '/') . '/kasta-' . substr(hash('sha256', home_url()), 0, 16);
        $existing = $path;
        while (! file_exists($existing)) {
            if (is_link($existing)) { throw new \RuntimeException('Storage symlinks are not supported.'); }
            $existing = dirname($existing);
        }
        // Resolve existing ancestors before creating anything; reject symlink redirection.
        for ($part = $path; $part !== '/'; $part = dirname($part)) {
            if (is_link($part)) { throw new \RuntimeException('Storage symlinks are not supported.'); }
        }
        $resolved = realpath($existing) . substr($path, strlen($existing));
        $documentRoot = ! empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        foreach (array_filter([$wpRoot, $documentRoot]) as $publicRoot) {
            $publicRoot = rtrim($publicRoot, '/');
            if ($resolved === $publicRoot || str_starts_with($resolved, $publicRoot . '/')) {
                throw new \RuntimeException('Kasta storage must be outside the web root. Configure MARUDERM_KASTA_STORAGE_DIR.');
            }
        }
        if ($create && ! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new \RuntimeException('Cannot create private Kasta storage.');
        }
        if ($create && is_dir($path) && ! chmod($path, 0700)) {
            throw new \RuntimeException('Cannot protect Kasta storage permissions.');
        }
        return $path;
    }

    public function path(string $name, bool $create = false): string
    {
        if (! in_array($name, ['products.xml', 'generation.lock'], true)) {
            throw new \RuntimeException('Unsupported storage file.');
        }
        $path = $this->directory($create) . '/' . $name;
        if (is_link($path)) { throw new \RuntimeException('Storage file symlinks are not supported.'); }
        return $path;
    }

    public function read(): string
    {
        $path = $this->path('products.xml');
        if (! is_file($path)) { return ''; }
        $xml = file_get_contents($path);
        if ($xml === false) { throw new \RuntimeException('Cannot read previous Kasta XML.'); }
        return $xml;
    }

    public function write(string $xml): void
    {
        $path = $this->path('products.xml', true);
        $temporary = tempnam(dirname($path), '.kasta-');
        if ($temporary === false) { throw new \RuntimeException('Cannot create temporary XML.'); }
        try {
            if (! chmod($temporary, 0600) || file_put_contents($temporary, $xml) !== strlen($xml)) {
                throw new \RuntimeException('Cannot write complete XML.');
            }
            if (! rename($temporary, $path)) { throw new \RuntimeException('Cannot publish XML atomically.'); }
        } finally {
            if (is_file($temporary)) { unlink($temporary); }
        }
    }
}
