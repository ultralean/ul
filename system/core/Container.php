<?php

namespace UltraLean\Core;

final class Container
{
    private static array $bindings = [];
    private static array $instances = [];
    private static array $shared = [];

    public static function bind(string $key, callable $resolver): void
    {
        self::$bindings[$key] = $resolver;
    }

    public static function singleton(string $key, callable $resolver): void
    {
        self::$bindings[$key] = $resolver;
        self::$shared[$key] = true;
    }

    public static function get(string $key)
    {
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        if (isset(self::$bindings[$key])) {
            $object = self::$bindings[$key]();

            if (isset(self::$shared[$key])) {
                self::$instances[$key] = $object;
            }

            return $object;
        }

        return self::$instances[$key] = new $key();
    }
}
