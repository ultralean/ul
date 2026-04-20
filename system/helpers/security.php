<?php

use UltraLean\Core\Session;

/* =========================
 * TOKEN
 * ========================= */

function csrf_token(): string
{
    static $token = null;

    if ($token !== null) return $token;

    $token = Session::get('_csrf_token');

    if (!$token) {
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf_token', $token);
    }

    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

/* =========================
 * VALIDATION
 * ========================= */

function csrf_should_skip(string $path): bool
{
    $except = config('security.csrf.except', []);

    foreach ($except as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return true;
        }
    }

    return false;
}

function csrf_validate(): bool
{
    $request = \UltraLean\Core\Request::instance();

    $header = config('security.csrf.header', 'X-CSRF-TOKEN');

    $token = $_POST['_csrf']
        ?? $request->header($header)
        ?? null;

    if (!$token) return false;

    return hash_equals(Session::get('_csrf_token', ''), $token);
}

function rate_limit(string $key, int $max, int $seconds): bool
{
    // Prefer APCu if available
    if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
        return rate_limit_apcu($key, $max, $seconds);
    }

    return rate_limit_file($key, $max, $seconds);
}

function api_protect(): void
{
    $key = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if ($key !== config('security.api.key')) {
        \UltraLean\Core\Response::error(401, 'Unauthorized');
    }
}

/* =========================
 * APCu (FASTEST)
 * ========================= */

function rate_limit_apcu(string $key, int $max, int $seconds): bool
{
    $cacheKey = 'rl:' . $key;
    $now = time();

    $data = apcu_fetch($cacheKey);

    if (!$data) {
        apcu_store($cacheKey, [1, $now], $seconds);
        return true;
    }

    [$count, $start] = $data;

    if ($now - $start > $seconds) {
        apcu_store($cacheKey, [1, $now], $seconds);
        return true;
    }

    if ($count >= $max) return false;

    apcu_store($cacheKey, [$count + 1, $start], $seconds);
    return true;
}

/* =========================
 * FILE (FALLBACK)
 * ========================= */

function rate_limit_file(string $key, int $max, int $seconds): bool
{
    $dir = STORAGE_PATH . '/rate_limit';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = $dir . '/' . md5($key);
    $now = time();

    if (mt_rand(1, 100) === 1) {
        rate_limit_cleanup($dir, $seconds);
    }

    $data = is_file($file)
        ? json_decode(file_get_contents($file), true)
        : null;

    if (!$data) {
        file_put_contents($file, json_encode([1, $now]), LOCK_EX);
        return true;
    }

    [$count, $start] = $data;

    if ($now - $start > $seconds) {
        file_put_contents($file, json_encode([1, $now]), LOCK_EX);
        return true;
    }

    if ($count >= $max) return false;

    file_put_contents($file, json_encode([$count + 1, $start]), LOCK_EX);
    return true;
}

function rate_limit_cleanup(string $dir, int $ttl): void
{
    $now = time();

    foreach (glob($dir . '/*') as $file) {

        // Skip if recently used
        if ($now - filemtime($file) <= $ttl) {
            continue;
        }

        @unlink($file);
    }
}
