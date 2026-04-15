<?php

namespace UltraLean\Core;

class Response
{
    protected static ?bool $forceJson = null;

    protected static function send(int $status, string $type): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: ' . $type);
            header('X-Request-ID: ' . REQUEST_ID);
        }
    }

    protected static function forceJson(): bool
    {
        if (self::$forceJson === null) {
            self::$forceJson = config('app.force_json', false);
        }

        return self::$forceJson;
    }

    /* =========================
     * OUTPUT
     * ========================= */

    public static function json($data, int $status = 200, bool $exit = true): void
    {
        self::send($status, 'application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($exit) exit;
    }

    public static function html(string $html, int $status = 200, bool $exit = true): void
    {
        if (self::forceJson()) {
            self::json(['html' => $html], $status, $exit);
            return;
        }

        self::send($status, 'text/html; charset=utf-8');
        echo $html;

        if ($exit) exit;
    }

    public static function text(string $text, int $status = 200, bool $exit = true): void
    {
        if (self::forceJson()) {
            self::json(['text' => $text], $status, $exit);
            return;
        }

        self::send($status, 'text/plain; charset=utf-8');
        echo $text;

        if ($exit) exit;
    }

    public static function redirect(string $url, int $status = 302, bool $exit = true): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $url);
        }

        if ($exit) exit;
    }

    public static function download(string $file, ?string $name = null): void
    {
        if (!is_file($file)) {
            self::text('File not found', 404);
        }

        $name ??= basename($file);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));

        readfile($file);
        exit;
    }

    public static function auto($data, int $status = 200): void
    {
        if (self::forceJson() || is_array($data)) {
            self::json($data, $status);
        }

        self::html((string)$data, $status);
    }

    public static function error(int $code, string $message = ''): void
    {
        // Optional logging (zero overhead if disabled)
        Logger::get()->error($message ?: "HTTP {$code}");

        if (config('app.env') === 'development') {
            self::text($message ?: "Error {$code}", $code);
        }

        self::text("Error {$code}", $code);
    }
}
