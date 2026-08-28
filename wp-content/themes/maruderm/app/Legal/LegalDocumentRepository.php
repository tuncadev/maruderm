<?php

declare(strict_types=1);

namespace Maruderm\Legal;

if (!defined('ABSPATH')) {
    exit();
}

/** Reads the reviewed, source-faithful legal document snapshot. */
final class LegalDocumentRepository
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $documents = null;

    /** @return array<string, mixed>|null */
    public function find(string $key): ?array
    {
        $documents = $this->all();

        return isset($documents[$key]) && is_array($documents[$key]) ? $documents[$key] : null;
    }

    /** @return array<string, array<string, mixed>> */
    private function all(): array
    {
        if ($this->documents !== null) {
            return $this->documents;
        }

        $path = __DIR__ . '/legal-documents.json';
        $decoded = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
        $this->documents = is_array($decoded) ? $decoded : [];

        return $this->documents;
    }
}
