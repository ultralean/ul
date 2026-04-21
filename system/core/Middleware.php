<?php

namespace UltraLean\Core;

class Middleware
{
    private static array $map = [];
    private static array $prototypes = [];
    private static array $controllers = [];

    private static function load(): void
    {
        if (self::$map) return;

        $cache = STORAGE_PATH . '/cache/middleware.php';
        $hashFile = STORAGE_PATH . '/cache/middleware.hash';

        $files = glob(APP_PATH . '/Middleware/*.php') ?: [];

        // 🔥 BUILD HASH (fast + reliable)
        $hash = '';
        foreach ($files as $f) {
            $hash .= $f . filemtime($f);
        }
        $hash = md5($hash);

        if (
            !is_file($cache) ||
            !is_file($hashFile) ||
            file_get_contents($hashFile) !== $hash
        ) {
            $map = [];

            foreach ($files as $file) {

                require_once $file;

                $class = 'App\\Middleware\\' . basename($file, '.php');

                if (class_exists($class) && method_exists($class, 'handle')) {
                    $map[basename($file, '.php')] = $class;
                }
            }

            // ✅ ENSURE DIRECTORY EXISTS
            $dir = dirname($cache);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            // ✅ WRITE CACHE
            file_put_contents(
                $cache,
                '<?php return ' . var_export($map, true) . ';'
            );

            file_put_contents($hashFile, $hash);
        }

        self::$map = require $cache;
    }

    public static function compile(array $names, string $handler): callable
    {
        self::load();

        [$class, $method] = explode('@', $handler, 2);
        $fqcn = 'App\\Controllers\\' . $class;

        if (!isset(self::$controllers[$fqcn])) {
            self::$controllers[$fqcn] = new $fqcn();
        }

        $controller = self::$controllers[$fqcn];

        $next = static function (...$params) use ($controller, $method) {
            $controller->$method(...$params);
        };

        foreach (array_reverse($names) as $name) {

            if (str_starts_with($name, 'throttle:')) {

                [$max, $sec] = array_map('intval', explode(',', explode(':', $name)[1]));

                $next = static function (...$params) use ($next, $max, $sec) {

                    if (!rate_limit($_SERVER['REMOTE_ADDR'] ?? 'x', $max, $sec)) {
                        \UltraLean\Core\Response::error(429);
                    }

                    return $next(...$params);
                };

                continue;
            }

            $mw = self::$map[$name] ?? null;

            if (!$mw) {
                throw new \RuntimeException("Middleware {$name} not found");
            }

            $next = static function (...$params) use ($mw, $next) {

                if (!isset(self::$prototypes[$mw])) {
                    self::$prototypes[$mw] = new $mw();
                }

                $m = clone self::$prototypes[$mw];

                if ($m->handle(\UltraLean\Core\Request::instance()) === false) {
                    return;
                }

                return $next(...$params);
            };
        }

        return $next;
    }
}
