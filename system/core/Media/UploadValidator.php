<?php

namespace UltraLean\Core\Media;

final class UploadValidator
{
    public function __construct(
        private int $maxBytes = 5242880,
        private array $allowed = []
    ) {}

    public function validate(int $size, string $mime): ?string
    {
        if ($size > $this->maxBytes) {
            return 'File too large';
        }

        if ($this->allowed && !in_array($mime, $this->allowed, true)) {
            return 'File type not allowed';
        }

        return null;
    }
}
