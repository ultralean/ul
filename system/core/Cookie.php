<?php

namespace UltraLean\Core;

class Cookie
{
    public static function set(string $name, string $value, array $options = []): void
    {
        // Inline defaults (avoid array_merge)
        $expires  = $options['expires']  ?? time() + 3600;
        $path     = $options['path']     ?? '/';
        $domain   = $options['domain']   ?? '';
        $secure   = $options['secure']   ?? (!empty($_SERVER['HTTPS']));
        $httponly = $options['httponly'] ?? true;
        $samesite = $options['samesite'] ?? 'Lax';

        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);
    }

    public static function get(string $name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function delete(string $name, array $options = []): void
    {
        $options['expires'] = time() - 3600;
        self::set($name, '', $options);
    }
}
