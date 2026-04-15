<?php

namespace UltraLean\Core\Media;

final class ImageProcessor
{
    public function process(string $file, string $mime): void
    {
        $this->fixOrientation($file, $mime);
        $this->resize($file, $mime, 1200, 1200);
    }

    private function fixOrientation(string $file, string $mime): void
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

        imagejpeg($img, $file, 85);
        imagedestroy($img);
    }

    private function resize(string $file, string $mime, int $maxW, int $maxH): void
    {
        $info = getimagesize($file);
        if (!$info) return;

        [$w, $h] = $info;

        $ratio = min($maxW / $w, $maxH / $h);
        if ($ratio >= 1) return;

        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);

        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file),
            'image/png'  => imagecreatefrompng($file),
            'image/webp' => imagecreatefromwebp($file),
            default => null
        };

        if (!$src) return;

        $dst = imagecreatetruecolor($nw, $nh);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        match ($mime) {
            'image/jpeg' => imagejpeg($dst, $file, 85),
            'image/png'  => imagepng($dst, $file),
            'image/webp' => imagewebp($dst, $file, 85),
        };

        imagedestroy($src);
        imagedestroy($dst);
    }
}
