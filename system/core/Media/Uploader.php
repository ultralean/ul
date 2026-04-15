<?php

namespace UltraLean\Core\Media;

final class Uploader
{
    private array $files;
    private string $path = '';
    private ?string $oldFile = null;

    public function __construct(string|array $input)
    {
        $this->files = is_string($input)
            ? ($_FILES[$input] ?? [])
            : $input;
    }

    public static function upload(string|array $input): self
    {
        return new self($input);
    }

    public function to(string $path): self
    {
        $this->path = rtrim($path, '/') . '/';
        return $this;
    }

    public function deleteOld(?string $file): self
    {
        $this->oldFile = $file;
        return $this;
    }

    public function save(
        FileNamer $namer,
        UploadValidator $validator,
        FileManager $manager,
        ?ImageProcessor $image = null
    ): array {

        if (!isset($this->files['tmp_name'])) {
            return ['success' => false, 'error' => 'No files'];
        }

        $manager->ensureDir($this->path);

        $multi = is_array($this->files['name']);
        $count = $multi ? count($this->files['name']) : 1;

        $stored = [];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $count; $i++) {

            $file = [
                'name'     => $multi ? $this->files['name'][$i] : $this->files['name'],
                'tmp_name' => $multi ? $this->files['tmp_name'][$i] : $this->files['tmp_name'],
                'size'     => $multi ? $this->files['size'][$i] : $this->files['size'],
                'error'    => $multi ? $this->files['error'][$i] : $this->files['error'],
            ];

            // ✅ Handle upload errors early (no extra syscalls later)
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => $this->codeToMessage($file['error'])];
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'error' => 'Invalid upload'];
            }

            // ✅ Real MIME detection (single syscall)
            $mime = finfo_file($finfo, $file['tmp_name']);

            if ($err = $validator->validate($file['size'], $mime)) {
                return ['success' => false, 'error' => $err];
            }

            $filename = $namer->make($file['name'], $mime);
            $target = $this->path . $filename;

            if (!move_uploaded_file($file['tmp_name'], $target)) {
                return ['success' => false, 'error' => 'Move failed'];
            }

            // ✅ Optional image processing
            if ($image && str_starts_with($mime, 'image/')) {
                $image->process($target, $mime);
            }

            $stored[] = [
                'name' => $filename,
                'size' => $file['size'],
                'mime' => $mime
            ];
        }

        finfo_close($finfo);

        $manager->delete($this->path, $this->oldFile);

        return [
            'success' => true,
            'files' => $stored
        ];
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds php.ini limit',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL    => 'Partial upload',
            UPLOAD_ERR_NO_FILE    => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Disk write failed',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension',
            default               => 'Unknown upload error',
        };
    }
}
