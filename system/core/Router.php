<?php

namespace UltraLean\Core;

class Router
{
    private static array $routes = [];
    private static array $groupMiddleware = [];
    private static string $groupPrefix = '';

    private static array $compiled = [];

    /* =========================
     * ROUTE REGISTRATION
     * ========================= */

    public static function add(string $method, string $uri, string $handler, array $middleware = []): void
    {
        $uri = self::$groupPrefix . $uri;
        $middleware = array_merge(self::$groupMiddleware, $middleware);

        self::$routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public static function get($uri, $handler, $middleware = [])
    {
        self::add('GET', $uri, $handler, $middleware);
    }
    public static function post($uri, $handler, $middleware = [])
    {
        self::add('POST', $uri, $handler, $middleware);
    }
    public static function put($uri, $handler, $middleware = [])
    {
        self::add('PUT', $uri, $handler, $middleware);
    }
    public static function delete($uri, $handler, $middleware = [])
    {
        self::add('DELETE', $uri, $handler, $middleware);
    }
    public static function patch($uri, $handler, $middleware = [])
    {
        self::add('PATCH', $uri, $handler, $middleware);
    }
    public static function head($uri, $handler, $middleware = [])
    {
        self::add('HEAD', $uri, $handler, $middleware);
    }
    public static function options($uri, $handler, $middleware = [])
    {
        self::add('OPTIONS', $uri, $handler, $middleware);
    }

    public static function group(array $opts, callable $cb): void
    {
        $prevPrefix = self::$groupPrefix;
        $prevMW = self::$groupMiddleware;

        if (!empty($opts['prefix'])) {
            self::$groupPrefix .= $opts['prefix'];
        }

        if (!empty($opts['middleware'])) {
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $opts['middleware']);
        }

        $cb();

        self::$groupPrefix = $prevPrefix;
        self::$groupMiddleware = $prevMW;
    }

    /* =========================
     * BOOT (COMPILE)
     * ========================= */

    public static function boot(): void
    {
        self::compile();
        self::$routes = require STORAGE_PATH . '/cache/routes.php';
    }

    /* =========================
     * COMPILE
     * ========================= */

    private static function compile(): void
    {
        $routesDir = APP_PATH . '/routes';
        $cacheDir  = STORAGE_PATH . '/cache';

        $cacheFile = $cacheDir . '/routes.php';
        $hashFile  = $cacheDir . '/routes.hash';

        // 1. Get all route files
        $files = glob($routesDir . '/*.php');

        // 2. Build hash (VERY FAST)
        $hashData = '';

        foreach ($files as $file) {
            $hashData .= $file . filemtime($file);
        }

        $currentHash = md5($hashData);

        // 3. Skip if unchanged
        if (
            is_file($cacheFile) &&
            is_file($hashFile) &&
            file_get_contents($hashFile) === $currentHash
        ) {
            return;
        }

        // 4. Ensure cache dir
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        // 5. Reset routes
        self::$routes = [];

        // 6. Load ALL route files automatically
        foreach ($files as $file) {
            require $file;
        }

        // 7. Compile routes (NO closures)
        $compiled = [];

        foreach (self::$routes as $r) {
            $compiled[] = [
                'method' => $r['method'],
                'regex'  => self::toRegex($r['uri']),
                'handler' => $r['handler'],   // STRING only
                'middleware' => $r['middleware'],
            ];
        }

        // 8. Save cache (SAFE)
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($compiled, true) . ';'
        );

        file_put_contents($hashFile, $currentHash);
    }

    private static function toRegex(string $uri): string
    {
        $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $uri);
        return '#^' . rtrim($pattern, '/') . '$#';
    }

    /* =========================
     * DISPATCH
     * ========================= */

    public static function dispatch(): void
    {
        if (config('security.rate_limit.enabled', false)) {

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'guest';

            if (!rate_limit(
                'global|' . $ip,
                config('security.rate_limit.max', 100),
                config('security.rate_limit.window', 60)
            )) {
                \UltraLean\Core\Response::error(429, 'Too Many Requests');
            }
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

        $routes = self::$compiled[$method] ?? [];

        foreach ($routes as $r) {

            if (preg_match($r['regex'], $uri, $matches)) {

                array_shift($matches);

                $pipeline = Middleware::runtime($r['pipeline']);
                $pipeline(...$matches);
                return;
            }
        }

        Response::text('404 Not Found', 404);
    }
}
