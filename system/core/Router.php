<?php

namespace UltraLean\Core;

class Router
{
    private static array $tree = [];
    private static array $static = [];

    private static array $routes = [];
    private static array $groupMiddleware = [];
    private static string $groupPrefix = '';

    /* ========================= */

    public static function add(string $method, string $uri, string $handler, array $mw = []): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'uri' => self::$groupPrefix . $uri,
            'handler' => $handler,
            'mw' => array_merge(self::$groupMiddleware, $mw),
        ];
    }

    public static function get($u, $h, $m = [])
    {
        self::add('GET', $u, $h, $m);
    }
    public static function post($u, $h, $m = [])
    {
        self::add('POST', $u, $h, $m);
    }
    public static function put($u, $h, $m = [])
    {
        self::add('PUT', $u, $h, $m);
    }
    public static function delete($u, $h, $m = [])
    {
        self::add('DELETE', $u, $h, $m);
    }
    public static function patch($u, $h, $m = [])
    {
        self::add('PATCH', $u, $h, $m);
    }
    public static function head($u, $h, $m = [])
    {
        self::add('HEAD', $u, $h, $m);
    }
    public static function options($u, $h, $m = [])
    {
        self::add('OPTIONS', $u, $h, $m);
    }

    public static function group(array $opt, callable $cb): void
    {
        $p = self::$groupPrefix;
        $m = self::$groupMiddleware;

        if (!empty($opt['prefix'])) self::$groupPrefix .= $opt['prefix'];
        if (!empty($opt['middleware'])) self::$groupMiddleware = array_merge(self::$groupMiddleware, $opt['middleware']);

        $cb();

        self::$groupPrefix = $p;
        self::$groupMiddleware = $m;
    }

    /* ========================= */

    public static function boot(): void
    {
        $cache = STORAGE_PATH . '/cache/routes.php';
        $hashFile = STORAGE_PATH . '/cache/routes.hash';

        $files = glob(APP_PATH . '/routes/*.php') ?: [];

        $hash = '';
        foreach ($files as $f) {
            $hash .= $f . filemtime($f) . filesize($f);
        }
        $hash = md5($hash);

        if (is_file($cache) && is_file($hashFile) && file_get_contents($hashFile) === $hash) {
            [$static, $tree] = require $cache;
            self::$static = $static;
            self::$tree = $tree;
            return;
        }

        // rebuild
        self::$routes = [];

        foreach ($files as $file) require $file;

        self::compile();

        if (!is_dir(STORAGE_PATH . '/cache')) {
            mkdir(STORAGE_PATH . '/cache', 0777, true);
        }

        file_put_contents($cache, '<?php return ' . var_export([self::$static, self::$tree], true) . ';');
        file_put_contents($hashFile, $hash);
    }

    private static function compile(): void
    {
        foreach (self::$routes as $r) {

            $method = $r['method'];
            $uri = $r['uri'];

            // ✅ Normalize URI correctly
            if ($uri !== '/') {
                $uri = '/' . trim($uri, '/');
            } else {
                $uri = '/';
            }

            // STATIC
            if (!str_contains($uri, '{')) {
                self::$static[$method][$uri] = [
                    'handler' => $r['handler'],
                    'mw' => $r['mw']
                ];
                continue;
            }

            // TREE BUILD
            $segments = explode('/', $uri);
            $node = &self::$tree[$method];

            foreach ($segments as $seg) {

                if (str_starts_with($seg, '{')) {
                    $node['*'] ??= [];
                    $node = &$node['*'];
                } else {
                    $node[$seg] ??= [];
                    $node = &$node[$seg];
                }
            }

            $node['_end'] = [
                'handler' => $r['handler'],
                'mw' => $r['mw']
            ];
        }
    }

    /* ========================= */

    public static function dispatch(): void
    {
        // GLOBAL RATE LIMIT
        if (config('security.rate_limit.enabled', false)) {
            if (!rate_limit(
                'g|' . ($_SERVER['REMOTE_ADDR'] ?? 'x'),
                config('security.rate_limit.max', 100),
                config('security.rate_limit.window', 60)
            )) {
                Response::error(429);
            }
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';

        // 🔥 STATIC O(1)
        if (isset(self::$static[$method][$uri])) {
            self::run(self::$static[$method][$uri], []);
            return;
        }

        // 🔥 TREE MATCH
        $segments = explode('/', trim($uri, '/'));
        $node = self::$tree[$method] ?? null;

        if (!$node) {
            Response::text('404', 404);
        }

        $params = [];

        foreach ($segments as $seg) {

            if (isset($node[$seg])) {
                $node = $node[$seg];
                continue;
            }

            if (isset($node['*'])) {
                $params[] = $seg;
                $node = $node['*'];
                continue;
            }

            Response::text('404', 404);
        }

        if (!isset($node['_end'])) {
            Response::text('404', 404);
        }

        self::run($node['_end'], $params);
    }

    private static function run(array $route, array $params): void
    {
        $pipeline = Middleware::compile($route['mw'], $route['handler']);
        $pipeline(...$params);
    }
}
