<?php

namespace UltraLean\Core;

class Session
{
    protected static bool $started = false;

    protected static function start(): void
    {
        if (!self::$started) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            self::$started = true;
        }
    }

    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function delete(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, $value = null)
    {
        self::start();

        if ($value === null) {
            $val = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $val;
        }

        $_SESSION['_flash'][$key] = $value;
    }

    public static function pull(string $key, $default = null)
    {
        self::start();

        if (!isset($_SESSION[$key])) {
            return $default;
        }

        $value = $_SESSION[$key];
        unset($_SESSION[$key]);

        return $value;
    }

    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];
        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        self::$started = false;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }
}
