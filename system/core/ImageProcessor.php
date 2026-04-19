<?php

namespace UltraLean\Core\Media;

final class ImageProcessor
{
    private ?array $resize = null;
    private ?array $thumb = null;
    private int $quality = 85;
    private bool $fixOrientation = true;

    public function resize(int $w, int $h): self
    {
        $this->resize = ['w' => $w, 'h' => $h];
        return $this;
    }

    public function thumbnail(int $w, int $h): self
    {
        $this->thumb = ['w' => $w, 'h' => $h];
        return $this;
    }

    public function quality(int $q): self
    {
        $this->quality = max(0, min(100, $q));
        return $this;
    }

    public function orient(bool $state = true): self
    {
        $this->fixOrientation = $state;
        return $this;
    }

    public function process(string $file, string $mime): void
    {
        if (!str_starts_with($mime, 'image/')) {
            return; // 🔒 safety: never process non-images
        }

        if ($this->fixOrientation) {
            $this->fixExifOrientation($file, $mime);
        }

        if ($this->resize) {
            $this->resizeImage($file, $mime, $this->resize);
        }

        if ($this->thumb) {
            $this->makeThumbnail($file, $mime, $this->thumb);
        }
    }

    /* ========================
     * Internals
     * ======================== */

    private function fixExifOrientation(string $file, string $mime): void
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($file);
        if (!$exif || empty($exif['Orientation'])) return;

        $img = imagecreatefromjpeg($file);

        $img = match ($exif['Orientation']) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img
        };

        imagejpeg($img, $file, $this->quality);
        imagedestroy($img);
    }

    private function resizeImage(string $file, string $mime, array $dim): void
    {
        [$w, $h] = getimagesize($file);

        $ratio = min($dim['w'] / $w, $dim['h'] / $h);
        if ($ratio >= 1) return;

        $this->resample($file, $mime, $w, $h, (int)($w * $ratio), (int)($h * $ratio));
    }

    private function makeThumbnail(string $file, string $mime, array $dim): void
    {
        [$w, $h] = getimagesize($file);

        $ratio = min($dim['w'] / $w, $dim['h'] / $h);

        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);

        $thumbPath = $this->appendToFilename($file, '_thumb');

        $this->resample($file, $mime, $w, $h, $nw, $nh, $thumbPath);
    }

    private function resample(
        string $file,
        string $mime,
        int $w,
        int $h,
        int $nw,
        int $nh,
        ?string $target = null
    ): void {
        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file),
            'image/png'  => imagecreatefrompng($file),
            'image/webp' => imagecreatefromwebp($file),
            default => null
        };

        if (!$src) return;

        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $out = $target ?? $file;

        match ($mime) {
            'image/jpeg' => imagejpeg($dst, $out, $this->quality),
            'image/png'  => imagepng($dst, $out),
            'image/webp' => imagewebp($dst, $out, $this->quality),
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    private function appendToFilename(string $file, string $suffix): string
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $name = pathinfo($file, PATHINFO_FILENAME);
        $dir = dirname($file);

        return $dir . '/' . $name . $suffix . '.' . $ext;
    }
}
