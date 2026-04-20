<?php

namespace UltraLean\Core;

class Response
{
    private static ?bool $forceJson = null;

    private static function send(int $status, string $type): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: ' . $type);
            header('X-Request-ID: ' . REQUEST_ID);

            if (config('security.csp.enabled', true)) {
                header('Content-Security-Policy: ' . config('security.csp.policy'));
            }
        }
    }

    private static function forceJson(): bool
    {
        return self::$forceJson ??= config('app.force_json', false);
    }

    public static function json($data, int $status = 200): void
    {
        self::send($status, 'application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function html(string $html, int $status = 200): void
    {
        if (self::forceJson()) {
            self::json(['html' => $html], $status);
        }

        self::send($status, 'text/html');
        echo $html;
        exit;
    }

    public static function text(string $text, int $status = 200): void
    {
        if (self::forceJson()) {
            self::json(['text' => $text], $status);
        }

        self::send($status, 'text/plain');
        echo $text;
        exit;
    }

    public static function redirect(string $url, int $status = 302): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $url);
        }
        exit;
    }

    public static function auto($data, int $status = 200): void
    {
        is_array($data)
            ? self::json($data, $status)
            : self::html((string)$data, $status);
    }

    public static function error(int $code, string $msg = ''): void
    {
        Logger::get()->error($msg ?: "HTTP {$code}");
        self::text($msg ?: "Error {$code}", $code);
    }
}
