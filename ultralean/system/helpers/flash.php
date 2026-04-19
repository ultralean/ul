<?php

// =========================
// 🔁 LIFECYCLE
// =========================

function flash_init(): void
{
    if (FLASH_USE_COOKIES) {
        return;
    }

    if (isset($_SESSION['_ul_flash_next'])) {
        $_SESSION['_ul_flash'] = $_SESSION['_ul_flash_next'];
        unset($_SESSION['_ul_flash_next']);
    } else {
        $_SESSION['_ul_flash'] ??= [];
    }
}

// =========================
// ⚡ INTERNAL STORE (CACHED)
// =========================

function _flash_store(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    if (FLASH_USE_COOKIES) {
        return $cache = _flash_cookie_decode();
    }

    return $cache = $_SESSION['_ul_flash'] ?? [];
}

function _flash_store_set(array $data): void
{
    if (FLASH_USE_COOKIES) {
        _flash_cookie_encode($data);
        return;
    }

    $_SESSION['_ul_flash_next'] = $data;
}

// =========================
// 🍪 COOKIE MODE
// =========================

function _flash_cookie_decode(): array
{
    if (empty($_COOKIE[FLASH_COOKIE_KEY])) {
        return [];
    }

    $raw = base64_decode($_COOKIE[FLASH_COOKIE_KEY], true);
    if (!$raw) return [];

    [$payload, $hash] = explode('|', $raw, 2) + [null, null];

    if (!$payload || !$hash) return [];

    $valid = hash_hmac('sha256', $payload, FLASH_COOKIE_SECRET);

    if (!hash_equals($valid, $hash)) {
        return [];
    }

    return json_decode($payload, true) ?? [];
}

function _flash_cookie_encode(array $data): void
{
    $payload = json_encode($data);
    $hash = hash_hmac('sha256', $payload, FLASH_COOKIE_SECRET);

    $value = base64_encode($payload . '|' . $hash);

    setcookie(
        FLASH_COOKIE_KEY,
        $value,
        time() + 60,
        '/',
        '',
        false,
        true
    );
}

function _flash_cookie_clear(): void
{
    setcookie(
        FLASH_COOKIE_KEY,
        '',
        time() - 3600,
        '/',
        '',
        false,
        true
    );
}

// =========================
// 📥 API
// =========================

function flash_get(string $key, mixed $default = null): mixed
{
    $data = _flash_store();
    return $data[$key] ?? $default;
}

function flash_set(string $key, mixed $value): void
{
    $data = _flash_store();
    $data[$key] = $value;

    _flash_store_set($data);
}

function flash_all(): array
{
    return _flash_store();
}

function flash_has(string $key): bool
{
    $data = _flash_store();
    return isset($data[$key]);
}

function flash_clear(): void
{
    if (FLASH_USE_COOKIES) {
        _flash_cookie_clear();
        return;
    }

    unset($_SESSION['_ul_flash'], $_SESSION['_ul_flash_next']);
}

// =========================
// 🔁 REDIRECT HELPERS
// =========================

function redirect_with_errors(string $url, array $errors, array $oldInput = []): never
{
    flash_set('validation_errors', $errors);

    if ($oldInput) {
        flash_set('old_input', $oldInput);
    }

    header("Location: $url", true, 302);
    exit;
}

function redirect_with_message(string $url, string $key, string $message, array $oldInput = []): never
{
    flash_set($key, $message);

    if ($oldInput) {
        flash_set('old_input', $oldInput);
    }

    header("Location: $url", true, 302);
    exit;
}
