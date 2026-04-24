<?php

namespace UltraLean\Core;

use RuntimeException;

abstract class Controller
{
    protected ?View $viewInstance = null;
    protected ?Request $request = null;
    protected static bool $checkFiles;

    public function __construct()
    {
        if (!isset(self::$checkFiles)) {
            self::$checkFiles = config('view_check_files', false);
        }
    }

    public function callAction(string $method, array $params = [])
    {
        if (!method_exists($this, $method)) {
            throw new RuntimeException("Method {$method} not found");
        }

        return $this->$method(...$params);
    }

    protected function request(): Request
    {
        return $this->request ??= Request::instance();
    }

    protected function input(string $key, $default = null)
    {
        return $this->request()->input($key, $default);
    }

    protected function get(string $key, $default = null)
    {
        return $this->request()->get($key, $default);
    }

    protected function post(string $key, $default = null)
    {
        return $this->request()->post($key, $default);
    }

    protected function json(): array
    {
        return $this->request()->json();
    }

    protected function redirect(string $url, int $status = 302): void
    {
        Response::redirect($url, $status);
    }

    protected function respond($data, int $status = 200): void
    {
        Response::auto($data, $status);
    }

    protected function abort(int $code = 404, string $message = ''): void
    {
        Response::error($code, $message);
    }

    protected function back(): void
    {
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    protected function url(string $path = ''): string
    {
        static $base = null;

        $base ??= rtrim(config('app.base_url'), '/');

        return $base . '/' . ltrim($path, '/');
    }

    protected function view(string $view, array $data = []): void
    {
        Response::html($this->getView()->render($view, $data));
    }

    protected function rawView(string $view, array $data = []): void
    {
        if ($data) {
            extract($data, EXTR_SKIP);
        }

        static $basePath = null;
        $basePath ??= APP_PATH . '/views/';

        $path = $view[0] === '/' ? substr($view, 1) : $view;

        Response::html((function () use ($basePath, $path) {
            ob_start();
            require $basePath . str_replace('.', '/', $path) . '.php';
            return ob_get_clean();
        })());
    }

    protected function getView(): View
    {
        return $this->viewInstance ??= new View(APP_PATH . '/views', self::$checkFiles);
    }
}
