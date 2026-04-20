<?php

namespace UltraLean\Core;

class Router
{
    private static array $routes = [];
    private static array $groupMiddleware = [];
    private static string $groupPrefix = '';

    private static array $compiled = [];

    /* =========================
     * ROUTE DEFINITIONS (DEV ONLY)
     * ========================= */

    public static function add(string $method, string $uri, $handler, array $middleware = []): void
    {
        $uri = self::$groupPrefix . $uri;

        self::$routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'handler' => is_string($handler) && str_contains($handler, '@')
                ? explode('@', $handler, 2)
                : $handler,
            'middleware' => array_merge(self::$groupMiddleware, $middleware),
        ];
    }

    public static function get($uri, $handler, $mw = [])
    {
        self::add('GET', $uri, $handler, $mw);
    }
    public static function post($uri, $handler, $mw = [])
    {
        self::add('POST', $uri, $handler, $mw);
    }
    public static function put($uri, $handler, $mw = [])
    {
        self::add('PUT', $uri, $handler, $mw);
    }
    public static function delete($uri, $handler, $mw = [])
    {
        self::add('DELETE', $uri, $handler, $mw);
    }

    public static function group(array $opts, callable $cb): void
    {
        $prevPrefix = self::$groupPrefix;
        $prevMw = self::$groupMiddleware;

        if (!empty($opts['prefix'])) {
            self::$groupPrefix .= $opts['prefix'];
        }

        if (!empty($opts['middleware'])) {
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $opts['middleware']);
        }

        $cb();

        self::$groupPrefix = $prevPrefix;
        self::$groupMiddleware = $prevMw;
    }

    /* =========================
     * BOOT
     * ========================= */

    public static function boot(): void
    {
        $cacheFile = STORAGE_PATH . '/cache/routes.php';
        $hashFile  = STORAGE_PATH . '/cache/routes.hash';

        $hash = self::routesHash();

        if (!is_file($cacheFile) || !is_file($hashFile) || file_get_contents($hashFile) !== $hash) {
            self::compile($cacheFile, $hashFile);
        }

        self::$compiled = require $cacheFile;
    }

    private static function routesHash(): string
    {
        $files = glob(APP_PATH . '/routes/*.php');

        $ctx = '';
        foreach ($files as $f) {
            $ctx .= filemtime($f);
        }

        return md5($ctx);
    }

    private static function compile(string $cacheFile, string $hashFile): void
    {
        self::$routes = [];

        require APP_PATH . '/routes/web.php';

        $compiled = [];

        foreach (self::$routes as $r) {

            $regex = self::compileUri($r['uri']);

            $pipeline = Middleware::compile($r['middleware'], $r['handler']);

            $compiled[$r['method']][] = [
                'regex' => $regex,
                'handler' => $pipeline,
            ];
        }

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0777, true);
        }

        file_put_contents($cacheFile, '<?php return ' . var_export($compiled, true) . ';');
        file_put_contents($hashFile, self::routesHash());
    }

    private static function compileUri(string $uri): string
    {
        $pattern = preg_replace('#\{([^}]+)\}#', '([^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /* =========================
     * DISPATCH
     * ========================= */

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

        $routes = self::$compiled[$method] ?? [];

        foreach ($routes as $r) {

            if (preg_match($r['regex'], $uri, $matches)) {

                array_shift($matches);

                $r['handler'](...$matches);
                return;
            }
        }

        Response::text('404 Not Found', 404);
    }
}
