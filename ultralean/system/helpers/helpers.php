<?php

use UltraLean\Core\I18n\Translator;
use UltraLean\Core\Session;
use UltraLean\Core\Cookie;
use UltraLean\Core\Response;

/**
 * Get an instance from the DI container.
 * 
 * @param string $key
 * @return mixed
 * 
 * @example
 * 
 * $db = app('db');
 * 
 * $validator = app(Validator::class);
 * 
 */
function app(string $key)
{
    return \UltraLean\Core\Container::get($key);
}

/**
 * Translate text using the current locale.
 * 
 * @param string $key
 * @param array $replace
 * @return string
 * 
 * @example
 * 
 * echo __('messages.welcome');
 * 
 * echo __('messages.user.greeting', [
 *     'name' => 'John'
 * ]);
 * 
 */
function __($key, array $replace = []): string
{
    static $t;

    if (!$t) {
        $t = app(Translator::class);
    }

    return $t->get($key, $replace);
}




/**
 * Escape output to prevent XSS (safe output).
 * 
 * @param string|null $value
 * @return string
 * 
 * @example
 * 
 * echo e($value);
 * 
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get or set a session value.
 */
function session(string $key, $value = null)
{
    return $value === null
        ? Session::get($key)
        : Session::set($key, $value);
}

function session_has(string $key): bool
{
    return Session::has($key);
}

function session_delete(string $key): void
{
    Session::delete($key);
}

/**
 * Get or set a cookie value.
 */
function cookie(string $name, $value = null, array $options = [])
{
    return $value === null
        ? Cookie::get($name)
        : Cookie::set($name, $value, $options);
}

function redirect(string $url, int $code = 302): void
{
    Response::redirect($url, $code);
}

/**
 * Dump variables and end script.
 */
function dd(...$vars): void
{
    $isCli = php_sapi_name() === 'cli';

    if (!$isCli) echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    if (!$isCli) echo '</pre>';

    exit;
}
