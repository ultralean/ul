<?php

namespace UltraLean\Core\Media;

final class FileManager
{
    public function delete(string $path, ?string $file): void
    {
        if ($file) {
            $full = $path . $file;
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    public function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}
