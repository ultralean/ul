<?php

namespace UltraLean\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\cachedDispatcher;

class Router
{
    private static array $routes = [];
    private static array $groupMiddleware = [];
    private static string $groupPrefix = '';
    private static array $namedRoutes = [];
    private static ?Dispatcher $dispatcher = null;

    private static ?string $baseUrl = null;
    private static bool $isProduction;

    /**
     * Init environment once
     */
    private static function init(): void
    {
        if (!isset(self::$isProduction)) {
            self::$isProduction = (config('app.env') ?? 'production') === 'production';
            self::$baseUrl = rtrim(config('app.base_url') ?? '', '/');
        }
    }

    /**
     * Generate URL from named route
     */
    public static function url(string $name, array $params = [], bool $absolute = false): ?string
    {
        self::init();

        $uri = self::$namedRoutes[$name] ?? null;
        if (!$uri) return null;

        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }

        $uri = preg_replace('/\{[^\/]+\}/', '', $uri);

        if ($absolute && self::$baseUrl !== '') {
            return self::$baseUrl . $uri;
        }

        return $uri;
    }

    /**
     * Add route
     */
    public static function add(string $method, string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        $uri = self::$groupPrefix . $uri;
        $middleware = array_merge(self::$groupMiddleware, $middleware);

        // Pre-parse handler (micro-opt)
        if (is_string($handler) && str_contains($handler, '@')) {
            $handler = explode('@', $handler, 2);
        }

        self::$routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        if ($name) {
            self::$namedRoutes[$name] = $uri;
        }
    }

    // HTTP shortcuts
    public static function get(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('GET', $uri, $handler, $middleware, $name);
    }
    public static function post(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('POST', $uri, $handler, $middleware, $name);
    }
    public static function put(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('PUT', $uri, $handler, $middleware, $name);
    }
    public static function delete(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('DELETE', $uri, $handler, $middleware, $name);
    }
    public static function patch(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('PATCH', $uri, $handler, $middleware, $name);
    }
    public static function head(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('HEAD', $uri, $handler, $middleware, $name);
    }
    public static function options(string $uri, $handler, array $middleware = [], ?string $name = null): void
    {
        self::add('OPTIONS', $uri, $handler, $middleware, $name);
    }

    /**
     * Group routes
     */
    public static function group(array $options, callable $callback): void
    {
        $prevPrefix = self::$groupPrefix;
        $prevMiddleware = self::$groupMiddleware;

        if (!empty($options['prefix'])) {
            self::$groupPrefix .= $options['prefix'];
        }

        if (!empty($options['middleware'])) {
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $options['middleware']);
        }

        $callback();

        self::$groupPrefix = $prevPrefix;
        self::$groupMiddleware = $prevMiddleware;
    }

    /**
     * Build dispatcher (optimized)
     */
    private static function getDispatcher(): Dispatcher
    {
        if (self::$dispatcher !== null) {
            return self::$dispatcher;
        }

        self::init();

        $routesHash = md5(json_encode(self::$routes));

        $cacheDir = STORAGE_PATH . '/cache/routes';
        $cacheFile = $cacheDir . "/routes_{$routesHash}.php";

        // Only build cache if missing
        if (!file_exists($cacheFile)) {

            // Ensure dir exists (only when needed)
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }

            $lockFile = $cacheDir . '/routes.lock';
            $lockHandle = fopen($lockFile, 'c');

            if ($lockHandle && flock($lockHandle, LOCK_EX)) {

                // Double check after lock
                if (!file_exists($cacheFile)) {

                    $tempFile = $cacheFile . '.' . uniqid('', true) . '.tmp';

                    cachedDispatcher(function (RouteCollector $r) {
                        foreach (self::$routes as $route) {
                            $r->addRoute($route['method'], $route['uri'], [
                                'handler' => $route['handler'],
                                'middleware' => $route['middleware'],
                            ]);
                        }
                    }, [
                        'cacheFile' => $tempFile,
                        'cacheDisabled' => false,
                    ]);

                    rename($tempFile, $cacheFile);

                    // Cleanup old cache files
                    foreach (glob($cacheDir . '/routes_*.php') as $file) {
                        if ($file !== $cacheFile) {
                            @unlink($file);
                        }
                    }
                }

                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }

        // Build dispatcher ONCE
        return self::$dispatcher = cachedDispatcher(function (RouteCollector $r) {
            foreach (self::$routes as $route) {
                $r->addRoute($route['method'], $route['uri'], [
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                ]);
            }
        }, [
            'cacheFile' => $cacheFile,
            'cacheDisabled' => !self::$isProduction,
        ]);
    }

    /**
     * Dispatch request
     */
    public static function dispatch(): void
    {
        self::init();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        // Remove base path
        if (self::$baseUrl !== '' && str_starts_with($uri, self::$baseUrl)) {
            $uri = substr($uri, strlen(self::$baseUrl));
        }

        $uri = '/' . ltrim($uri, '/');

        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        /* =========================
     * 🚦 RATE LIMIT (VERY EARLY)
     * ========================= */
        if (config('security.rate_limit.enabled', false)) {

            // 🔥 Fast key (IP only, no extra work)
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'guest';

            if (!rate_limit(
                $ip,
                config('security.rate_limit.max', 100),
                config('security.rate_limit.window', 60)
            )) {
                \UltraLean\Core\Response::error(429, 'Too Many Requests');
            }
        }

        $routeInfo = self::getDispatcher()->dispatch($method, $uri);

        switch ($routeInfo[0]) {

            case Dispatcher::NOT_FOUND:
                http_response_code(404);
                echo '404 Not Found';
                return;

            case Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);
                echo '405 Method Not Allowed';
                return;

            case Dispatcher::FOUND:

                $handler = $routeInfo[1]['handler'];
                $middlewares = $routeInfo[1]['middleware'] ?? [];
                $vars = $routeInfo[2];

                /* =========================
             * 🔐 CSRF (BEFORE MIDDLEWARE)
             * ========================= */
                if (config('security.csrf.enabled', false)) {

                    // 🔥 Skip configured routes (like /api)
                    if (!csrf_should_skip($uri)) {

                        // Only validate state-changing methods
                        if (
                            $method === 'POST' ||
                            $method === 'PUT' ||
                            $method === 'PATCH' ||
                            $method === 'DELETE'
                        ) {
                            if (!csrf_validate()) {
                                \UltraLean\Core\Response::error(419, 'CSRF token mismatch');
                            }
                        }
                    }
                }

                /* =========================
             * 🧱 MIDDLEWARE
             * ========================= */
                if (!\UltraLean\Core\Middleware::run($middlewares)) {
                    return;
                }

                // Static controller instance cache
                static $instances = [];

                if (is_array($handler)) {
                    [$class, $methodName] = $handler;

                    if (is_string($class)) {
                        $fqcn = self::resolveControllerClass($class);

                        if (!isset($instances[$fqcn])) {
                            $instances[$fqcn] = new $fqcn();
                        }

                        $instances[$fqcn]->$methodName(...array_values($vars));
                        return;
                    }

                    $class->$methodName(...array_values($vars));
                    return;
                }

                if (is_callable($handler)) {
                    $handler(...array_values($vars));
                    return;
                }

                throw new \RuntimeException('Invalid route handler.');
        }
    }

    /**
     * Resolve controller
     */
    private static function resolveControllerClass(string $short): string
    {
        $fqcn = 'App\\Controllers\\' . ltrim($short, '\\');

        if (!self::$isProduction && !class_exists($fqcn)) {
            throw new \RuntimeException("Controller {$fqcn} not found.");
        }

        return $fqcn;
    }
}
