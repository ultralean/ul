<?php

namespace UltraLean\Core\Media;

final class FileNamer
{
    public function __construct(
        private string $prefix = '',
        private bool $autoPrefix = true,
        private bool $useDate = false,
        private string $dateFormat = 'Ymd'
    ) {}

    public function make(string $original, string $mime): string
    {
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        // ✅ sanitize filename (no extra syscalls)
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($original, PATHINFO_FILENAME)) ?: 'file';

        $prefix = $this->buildPrefix($mime);

        // ✅ no collision check needed (random is enough)
        return $prefix . $base . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    }

    private function buildPrefix(string $mime): string
    {
        $parts = [];

        if ($this->autoPrefix) {
            $parts[] = str_starts_with($mime, 'image/') ? 'img'
                : (str_starts_with($mime, 'video/') ? 'vid' : 'file');
        }

        if ($this->prefix) {
            $parts[] = $this->prefix;
        }

        if ($this->useDate) {
            $parts[] = date($this->dateFormat);
        }

        return $parts ? implode('_', $parts) . '_' : '';
    }
}
