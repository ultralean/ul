<?php

namespace UltraLean\Core;

use RuntimeException;

class Middleware
{
    protected array $middlewares = [];
    protected bool $loaded = false;

    public function register(string $name, callable $callback): void
    {
        $this->middlewares[$name] = $callback;
    }

    /**
     * Load middleware from cache or discover
     */
    public function load(): void
    {
        if ($this->loaded) return;

        $cacheFile = STORAGE_PATH . '/cache/middleware.php';

        // ⚡ Load from cache (FAST PATH)
        if (file_exists($cacheFile)) {
            $this->middlewares = require $cacheFile;
            $this->loaded = true;
            return;
        }

        // ⚡ Discover (only once)
        $map = [];

        foreach (glob(APP_PATH . '/Middleware/*.php') as $file) {
            require_once $file;

            $class = 'App\\Middleware\\' . basename($file, '.php');

            if (class_exists($class) && method_exists($class, 'handle')) {

                $name = basename($file, '.php');

                // 🔥 DI RESOLVED INSTANCE
                $map[$name] = function ($request, $next = null) use ($class) {
                    $instance = app($class);
                    return $instance->handle($request, $next);
                };
            }
        }

        // Save cache
        if (!is_dir(STORAGE_PATH . '/cache')) {
            mkdir(STORAGE_PATH . '/cache', 0777, true);
        }

        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($map, true) . ';'
        );

        $this->middlewares = $map;
        $this->loaded = true;
    }

    /**
     * Simple run (fast path)
     */
    public function run(array $names): bool
    {
        $this->load();

        $request = Request::instance();

        foreach ($names as $name) {
            $middleware = $this->middlewares[$name] ?? null;

            if (!$middleware) {
                throw new RuntimeException("Middleware '{$name}' not found.");
            }

            if ($middleware($request) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Optional pipeline (advanced usage)
     */
    public function pipeline(array $names, callable $core)
    {
        $this->load();

        $request = Request::instance();

        $pipeline = array_reduce(
            array_reverse($names),
            fn($next, $name) => function () use ($name, $next, $request) {

                $middleware = $this->middlewares[$name] ?? null;

                if (!$middleware) {
                    throw new RuntimeException("Middleware '{$name}' not found.");
                }

                return $middleware($request, $next);
            },
            $core
        );

        return $pipeline();
    }
}
