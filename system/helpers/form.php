<?php

/**
 * UltraLean Form Binding Layer
 *
 * Responsibilities:
 * - Old input handling
 * - Validation error display
 * - Form field helpers
 * - HTML-safe output
 *
 * Depends ONLY on:
 * - flash_get()
 */

/* =========================
   🧠 OLD INPUT
========================= */

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

/* =========================
   ❗ VALIDATION ERRORS
========================= */

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

function error_all(string $key): array
{
    $errors = errors();
    return $errors[$key] ?? [];
}

function error_text(string $key, string $wrapper = '<div class="error">%s</div>'): string
{
    $err = error($key);

    return $err
        ? sprintf($wrapper, htmlspecialchars($err, ENT_QUOTES, 'UTF-8'))
        : '';
}

function error_class(string $key, string $class = 'is-invalid'): string
{
    return has_error($key) ? $class : '';
}

/* =========================
   🧾 INPUT HELPERS
========================= */

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

/* =========================
   🧾 TEXTAREA
========================= */

function form_textarea(string $key, mixed $default = ''): string
{
    return old($key, $default);
}

/* =========================
   ☑ CHECKBOX / RADIO
========================= */

function form_checked(string $key, mixed $value): string
{
    $old = flash_get('old_input', []);

    if (is_array($old[$key] ?? null)) {
        return in_array($value, $old[$key], true) ? 'checked' : '';
    }

    return ((string)($old[$key] ?? '') === (string)$value) ? 'checked' : '';
}

function form_selected(string $key, mixed $value): string
{
    $old = flash_get('old_input', []);

    return ((string)($old[$key] ?? '') === (string)$value) ? 'selected' : '';
}

/* =========================
   📦 COMPOSITE HELPERS
========================= */

function form_input(string $key, mixed $default = ''): string
{
    return 'value="' . old($key, $default) . '"';
}

function form_label(string $text, string $for = ''): string
{
    return '<label for="' . htmlspecialchars($for, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        . '</label>';
}

/* =========================
   🧠 FIELD WRAPPERS (OPTIONAL UX LAYER)
========================= */

function field_error(string $key): string
{
    return error_text($key);
}

function field_class(string $key, string $baseClass = ''): string
{
    $errorClass = has_error($key) ? ' is-invalid' : '';
    return trim($baseClass . $errorClass);
}
