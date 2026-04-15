<?php

namespace UltraLean\Core;

class Request
{
    protected array $query;
    protected array $body;
    protected array $files;
    protected array $server;

    protected ?array $json = null;
    protected array $headerCache = [];

    protected static ?self $instance = null;

    public function __construct()
    {
        $this->query  = $_GET;
        $this->body   = $_POST;
        $this->files  = $_FILES;
        $this->server = $_SERVER;
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /* =========================
     * INPUT
     * ========================= */

    public function input(string $key, $default = null)
    {
        return $this->body[$key]
            ?? $this->query[$key]
            ?? $this->json()[$key]
            ?? $default;
    }

    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }

    public function json(): array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input');
            $this->json = $raw ? (json_decode($raw, true) ?: []) : [];
        }

        return $this->json;
    }

    public function all(): array
    {
        return $this->body + $this->query + $this->json();
    }

    /* =========================
     * META
     * ========================= */

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function header(string $key): ?string
    {
        if (isset($this->headerCache[$key])) {
            return $this->headerCache[$key];
        }

        $k = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->headerCache[$key] = $this->server[$k] ?? null;
    }

    public function isJson(): bool
    {
        return str_contains($this->server['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', 'application/json');
    }

    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function path(): string
    {
        $uri = $this->uri();
        $pos = strpos($uri, '?');

        return $pos === false ? $uri : substr($uri, 0, $pos);
    }

    /* =========================
     * FILES
     * ========================= */

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function moveFile(string $key, ?string $name = null): ?string
    {
        $file = $this->file($key);

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $path = rtrim(config('uploads_path'), '/') . '/';
        $name ??= basename($file['name']);

        $target = $path . $name;

        return move_uploaded_file($file['tmp_name'], $target) ? $target : null;
    }
}
