<?php

namespace UltraLean\Core;

class Middleware
{
    private static array $map = [];

    private static function load(): void
    {
        if (self::$map) return;

        $cacheDir  = STORAGE_PATH . '/cache';
        $cacheFile = $cacheDir . '/middleware.php';
        $hashFile  = $cacheDir . '/middleware.hash';

        // Ensure cache dir exists
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        // 🔥 Generate hash from middleware files (FAST + ACCURATE)
        $files = glob(APP_PATH . '/Middleware/*.php') ?: [];

        $hashData = '';

        foreach ($files as $file) {
            $hashData .= filemtime($file);
        }

        $currentHash = md5($hashData);

        // ✅ FAST PATH: cache valid
        if (is_file($cacheFile) && is_file($hashFile)) {
            if (file_get_contents($hashFile) === $currentHash) {
                self::$map = require $cacheFile;
                return;
            }
        }

        // ❗ REBUILD CACHE
        self::$map = self::buildCache($files);

        // Write cache (OPcache friendly)
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export(self::$map, true) . ';'
        );

        file_put_contents($hashFile, $currentHash);
    }

    private static function buildCache(array $files): array
    {
        $map = [];

        foreach ($files as $file) {

            require_once $file;

            $class = 'App\\Middleware\\' . basename($file, '.php');

            if (class_exists($class) && method_exists($class, 'handle')) {
                $name = basename($file, '.php');

                $map[$name] = $class;
            }
        }

        return $map;
    }

    public static function compile(array $names, $handler): callable
    {
        self::load();

        $next = self::resolveHandler($handler);

        foreach (array_reverse($names) as $name) {

            // THROTTLE INLINE (NO CLASS)
            if (str_starts_with($name, 'throttle:')) {

                [$max, $sec] = array_map('intval', explode(',', explode(':', $name)[1]));

                $next = function (...$args) use ($next, $max, $sec) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'guest';

                    if (!rate_limit($ip, $max, $sec)) {
                        Response::error(429, 'Too Many Requests');
                    }

                    return $next(...$args);
                };

                continue;
            }

            $class = self::$map[$name];

            $next = function (...$args) use ($class, $next) {
                $instance = new $class();

                if ($instance->handle(Request::instance()) === false) {
                    return null;
                }

                return $next(...$args);
            };
        }

        return $next;
    }

    private static function resolveHandler($handler): callable
    {
        if (is_array($handler)) {
            return function (...$args) use ($handler) {
                static $instances = [];

                [$class, $method] = $handler;

                if (!isset($instances[$class])) {
                    $instances[$class] = new $class();
                }

                return $instances[$class]->$method(...$args);
            };
        }

        return $handler;
    }
}
