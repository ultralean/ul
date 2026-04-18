<?php

/**
 * UltraLean Flash + Form Binding System
 *
 * Features:
 * - Session-based flash (default, industry standard)
 * - Stateless cookie mode (optional)
 * - Auto form binding helpers
 * - Old input + validation errors
 * - Secure + ultra-fast
 */

// =========================
// ⚙️ CONFIG
// =========================

if (!defined('FLASH_USE_COOKIES')) {
    define('FLASH_USE_COOKIES', false); // true = stateless mode
}

if (!defined('FLASH_COOKIE_KEY')) {
    define('FLASH_COOKIE_KEY', '_flash');
}

if (!defined('FLASH_COOKIE_SECRET')) {
    define('FLASH_COOKIE_SECRET', 'change_this_secret');
}

// =========================
// 🚀 INIT
// =========================

// =========================
// 🔁 LIFECYCLE
// =========================

function flash_init(): void
{
    if (FLASH_USE_COOKIES) {
        return;
    }

    $_SESSION['_flash'] = $_SESSION['_flash_next'] ?? [];
    unset($_SESSION['_flash_next']);
}

// =========================
// 📦 INTERNAL (COOKIE MODE)
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
    setcookie(FLASH_COOKIE_KEY, '', time() - 3600, '/');
}

// =========================
// 📥 FLASH GET/SET
// =========================

function flash_get(string $key, mixed $default = null): mixed
{
    if (FLASH_USE_COOKIES) {
        $data = _flash_cookie_decode();
        return $data[$key] ?? $default;
    }

    return $_SESSION['_flash'][$key] ?? $default;
}

function flash_set(string $key, mixed $value): void
{
    if (FLASH_USE_COOKIES) {
        $data = _flash_cookie_decode();
        $data[$key] = $value;
        _flash_cookie_encode($data);
        return;
    }

    $_SESSION['_flash_next'][$key] = $value;
}

function flash_all(): array
{
    if (FLASH_USE_COOKIES) {
        return _flash_cookie_decode();
    }

    return $_SESSION['_flash'] ?? [];
}

function flash_has(string $key): bool
{
    if (FLASH_USE_COOKIES) {
        $data = _flash_cookie_decode();
        return isset($data[$key]);
    }

    return isset($_SESSION['_flash'][$key]);
}

function flash_clear(): void
{
    if (FLASH_USE_COOKIES) {
        _flash_cookie_clear();
        return;
    }

    unset($_SESSION['_flash'], $_SESSION['_flash_next']);
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

// =========================
// 🧠 OLD INPUT
// =========================

function old(string $key, mixed $default = ''): string
{
    $old = flash_get('old_input', []);

    $value = $old[$key] ?? $default ?? '';

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function old_raw(string $key, mixed $default = null): mixed
{
    $old = flash_get('old_input', []);

    return $old[$key] ?? $default;
}

// =========================
// 🧾 FORM BINDING
// =========================

function form_value(string $key, mixed $default = ''): string
{
    return 'value="' . old($key, $default) . '"';
}

function form_text(string $key, mixed $default = ''): string
{
    return form_value($key, $default);
}

function form_email(string $key, mixed $default = ''): string
{
    return form_value($key, $default);
}

function form_password(): string
{
    return ''; // never repopulate passwords
}

function form_textarea(string $key, mixed $default = ''): string
{
    return old($key, $default);
}

function form_checked(string $key, mixed $value): string
{
    $old = flash_get('old_input', []);

    if (is_array($old[$key] ?? null)) {
        return in_array($value, $old[$key]) ? 'checked' : '';
    }

    return ((string)($old[$key] ?? '') === (string)$value) ? 'checked' : '';
}

function form_selected(string $key, mixed $value): string
{
    $old = flash_get('old_input', []);

    return ((string)($old[$key] ?? '') === (string)$value) ? 'selected' : '';
}

function form_file(): string
{
    return ''; // cannot repopulate file inputs
}

// =========================
// ❗ VALIDATION ERRORS
// =========================

function errors(): array
{
    return flash_get('validation_errors', []);
}

function error(string $key): ?string
{
    $errors = errors();
    return $errors[$key][0] ?? null;
}

function has_error(string $key): bool
{
    $errors = errors();
    return isset($errors[$key]);
}

function error_class(string $key, string $class = 'is-invalid'): string
{
    return has_error($key) ? $class : '';
}

function error_text(string $key, string $wrapper = '<div class="error">%s</div>'): string
{
    $err = error($key);

    return $err ? sprintf($wrapper, htmlspecialchars($err, ENT_QUOTES, 'UTF-8')) : '';
}
