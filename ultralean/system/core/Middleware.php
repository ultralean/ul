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

        foreach ($names as $name) {

            /* =========================
             * THROTTLE (INLINE, FAST)
             * ========================= */
            if (str_starts_with($name, 'throttle:')) {

                // Parse "throttle:5,60"
                $parts = explode(':', $name, 2);

                if (!isset($parts[1])) {
                    throw new RuntimeException("Invalid throttle definition: {$name}");
                }

                [$max, $seconds] = array_map(
                    'intval',
                    explode(',', $parts[1]) + [0, 0]
                );

                if ($max <= 0 || $seconds <= 0) {
                    throw new RuntimeException("Invalid throttle values: {$name}");
                }

                // Build smart key (IP + path + method)
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'guest';

                // Use normalized path (avoid query string abuse)
                $uri = $request->path();

                $method = $request->method();

                $key = $ip . '|' . $uri . '|' . $method;

                if (!rate_limit($key, $max, $seconds)) {
                    \UltraLean\Core\Response::error(429, 'Too Many Requests');
                }

                continue; // skip normal middleware resolution
            }

            /* =========================
             * NORMAL MIDDLEWARE
             * ========================= */
            $class = self::$map[$name] ?? null;

            if (!$class) {
                throw new RuntimeException("Middleware '{$name}' not found.");
            }

            $instance = new $class();

            if ($instance->handle($request) === false) {
                return false;
            }
        }

        return true;
    }
}
