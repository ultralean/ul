<?php

namespace UltraLean\Core;

use RuntimeException;

class Middleware
{
    private static array $map = [];
    private static bool $loaded = false;

    /**
     * Load middleware map (FAST PATH)
     */
    private static function load(): void
    {
        if (self::$loaded) return;

        $cacheFile = STORAGE_PATH . '/cache/middleware.php';

        // ✅ 1. Load from cache (FASTEST)
        if (is_file($cacheFile)) {
            self::$map = require $cacheFile;
            self::$loaded = true;
            return;
        }

        // ❗ 2. Build cache (ONLY ONCE)
        $map = [];

        foreach (glob(APP_PATH . '/Middleware/*.php') as $file) {

            require_once $file;

            $class = 'App\\Middleware\\' . basename($file, '.php');

            if (class_exists($class) && method_exists($class, 'handle')) {
                $name = basename($file, '.php');

                // ✅ store ONLY class name (no closures!)
                $map[$name] = $class;
            }
        }

        // Ensure cache dir
        if (!is_dir(STORAGE_PATH . '/cache')) {
            mkdir(STORAGE_PATH . '/cache', 0777, true);
        }

        // ✅ Write cache (pure PHP array = OPcache friendly)
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($map, true) . ';'
        );

        self::$map = $map;
        self::$loaded = true;
    }

    /**
     * Run middleware stack (HOT PATH)
     */
    public static function run(array $names): bool
    {
        self::load();

        if (!$names) return true;

        $request = Request::instance();

        // 🔥 Ultra-fast loop (no extra abstractions)
        foreach ($names as $name) {

            $class = self::$map[$name] ?? null;

            if (!$class) {
                throw new RuntimeException("Middleware '{$name}' not found.");
            }

            // Direct instantiation (or swap with container if needed)
            $instance = new $class();

            // Direct call (fastest possible)
            if ($instance->handle($request) === false) {
                return false;
            }
        }

        return true;
    }
}
