<?php

namespace UltraLean\Core\Media;

final class Uploader
{
    private array $files;
    private string $path;

    private array $allowed = [];
    private int $maxBytes = 5242880;

    private ?string $prefix = null;
    private ?string $postfix = null;

    private bool $autoPrefix = true;
    private bool $autoPostfix = false;

    private bool $useDate = false;
    private string $dateFormat = 'Ymd';

    private ?string $oldFile = null;
    private ?ImageProcessor $processor = null;

    private const IMAGE = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const VIDEO = ['video/mp4', 'video/webm'];
    private const DOC   = ['application/pdf', 'text/plain'];

    private const TYPE_PREFIX = [
        'image' => 'img',
        'video' => 'vid',
        'document' => 'doc',
    ];

    public function __construct(string|array $input)
    {
        $this->files = is_string($input)
            ? ($_FILES[$input] ?? [])
            : $input;

        $this->path = rtrim(config('uploads_path'), '/') . '/';
    }

    public static function file(string|array $input): self
    {
        return new self($input);
    }

    /* ===== Fluent ===== */

    public function to(string $path): self
    {
        $this->path = rtrim($path, '/') . '/';
        return $this;
    }

    public function image(): self
    {
        $this->allowed = self::IMAGE;
        return $this;
    }
    public function video(): self
    {
        $this->allowed = self::VIDEO;
        return $this;
    }
    public function document(): self
    {
        $this->allowed = self::DOC;
        return $this;
    }

    public function max(int $mb): self
    {
        $this->maxBytes = $mb * 1024 * 1024;
        return $this;
    }

    public function prefix(string $v): self
    {
        $this->prefix = $v;
        return $this;
    }
    public function postfix(string $v): self
    {
        $this->postfix = $v;
        return $this;
    }

    public function autoPrefix(bool $v = true): self
    {
        $this->autoPrefix = $v;
        return $this;
    }
    public function autoPostfix(bool $v = true): self
    {
        $this->autoPostfix = $v;
        return $this;
    }

    public function date(string $f = 'Ymd'): self
    {
        $this->useDate = true;
        $this->dateFormat = $f;
        return $this;
    }

    public function deleteOld(?string $f): self
    {
        $this->oldFile = $f;
        return $this;
    }

    public function processWith(?ImageProcessor $p): self
    {
        $this->processor = $p;
        return $this;
    }

    /* ===== Save ===== */

    public function save(): array
    {
        if (!isset($this->files['tmp_name'])) {
            return ['success' => false, 'error' => 'No file'];
        }

        if (!is_dir($this->path)) {
            mkdir($this->path, 0775, true);
        }

        $multi = is_array($this->files['name']);
        $count = $multi ? count($this->files['name']) : 1;

        $stored = [];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $count; $i++) {

            $tmp  = $multi ? $this->files['tmp_name'][$i] : $this->files['tmp_name'];
            $name = $multi ? $this->files['name'][$i] : $this->files['name'];
            $size = $multi ? $this->files['size'][$i] : $this->files['size'];
            $err  = $multi ? $this->files['error'][$i] : $this->files['error'];

            if ($err !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => 'Upload error'];
            }

            if (!is_uploaded_file($tmp)) {
                return ['success' => false, 'error' => 'Invalid upload'];
            }

            $mime = finfo_file($finfo, $tmp);

            if ($size > $this->maxBytes) {
                return ['success' => false, 'error' => 'File too large'];
            }

            if ($this->allowed && !in_array($mime, $this->allowed, true)) {
                return ['success' => false, 'error' => 'Invalid type'];
            }

            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $base = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($name, PATHINFO_FILENAME)) ?: 'file';

            $filename = $this->buildName($base, $ext, $mime);
            $target = $this->path . $filename;

            if (!move_uploaded_file($tmp, $target)) {
                return ['success' => false, 'error' => 'Move failed'];
            }

            // ✅ process only if image + processor exists
            if ($this->processor && str_starts_with($mime, 'image/')) {
                $this->processor->process($target, $mime);
            }

            $stored[] = $filename;
        }

        finfo_close($finfo);

        if ($this->oldFile) {
            $old = $this->path . $this->oldFile;
            if (is_file($old)) {
                @unlink($old);
            }
        }

        return ['success' => true, 'files' => $stored];
    }

    /* ===== Internals ===== */

    private function buildName(string $base, string $ext, string $mime): string
    {
        $prefix = [];

        if ($this->autoPrefix) {
            $type = str_starts_with($mime, 'image/') ? 'image'
                : (str_starts_with($mime, 'video/') ? 'video' : 'document');

            $prefix[] = self::TYPE_PREFIX[$type];
        }

        if ($this->prefix) $prefix[] = $this->prefix;
        if ($this->useDate) $prefix[] = date($this->dateFormat);

        $postfix = [];

        if ($this->autoPostfix) $postfix[] = time();
        if ($this->postfix) $postfix[] = $this->postfix;

        return ($prefix ? implode('_', $prefix) . '_' : '') .
            $base . '_' . bin2hex(random_bytes(5)) .
            ($postfix ? '_' . implode('_', $postfix) : '') .
            '.' . $ext;
    }
}
