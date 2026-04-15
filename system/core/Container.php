<?php

namespace UltraLean\Core;

class Container
{
    protected static array $bindings = [];
    protected static array $instances = [];

    public static function bind(string $key, callable $resolver): void
    {
        self::$bindings[$key] = $resolver;
    }

    public static function singleton(string $key, callable $resolver): void
    {
        self::$bindings[$key] = function () use ($key, $resolver) {
            return self::$instances[$key] ??= $resolver();
        };
    }

    public static function get(string $key)
    {
        // 🔥 cached instance
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        // 🔥 bound resolver
        if (isset(self::$bindings[$key])) {
            return self::$instances[$key] = self::$bindings[$key]();
        }

        // 🔥 fallback (no reflection)
        return self::$instances[$key] = new $key();
    }
}
