<?php

namespace UltraLean\Core;

class Request
{
    protected static ?self $instance = null;

    protected array $query;
    protected array $body;
    protected array $files;
    protected array $server;

    protected ?array $json = null;
    protected array $headers = [];

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

    public function input(string $key, $default = null)
    {
        return $this->body[$key]
            ?? $this->query[$key]
            ?? ($this->json()[$key] ?? null)
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
            $this->json = $raw ? json_decode($raw, true) ?? [] : [];
        }
        return $this->json;
    }

    public function all(): array
    {
        return $this->body + $this->query + $this->json();
    }

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function header(string $key): ?string
    {
        return $this->headers[$key]
            ??= $this->server['HTTP_' . strtoupper(str_replace('-', '_', $key))] ?? null;
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
        return strtok($uri, '?');
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }
}
