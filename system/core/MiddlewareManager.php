<?php

namespace UltraLean\Core;

class MiddlewareManager
{
    private static ?MiddlewareManager $instance = null; // <- Manager instance
    private Middleware $middleware;
    private bool $discovered = false;

    private function __construct()
    {
        $this->middleware = new Middleware();
    }

    // Singleton accessor
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    // Auto-discover middleware classes
    private function discover(): void
    {
        if ($this->discovered) return;

        $path = APP_PATH . '/Middleware';

        foreach (glob($path . '/*.php') as $file) {

            require_once $file;

            $class = 'App\\Middleware\\' . basename($file, '.php');

            if (class_exists($class) && method_exists($class, 'handle')) {

                $name = basename($file, '.php');

                $this->middleware->register($name, [new $class(), 'handle']);
            }
        }

        $this->discovered = true;
    }

    // Run middleware by names
    public function run(array $names): bool
    {
        $this->discover();
        return $this->middleware->run($names);
    }
}
