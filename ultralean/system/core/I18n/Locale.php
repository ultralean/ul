<?php

namespace UltraLean\Core\I18n;

class Locale
{
    protected static ?string $locale = null;

    protected static array $supported = [];
    protected static string $default;

    /**
     * Initialize locale for the request
     */
    public static function init(): void
    {
        if (self::$locale !== null) {
            return;
        }

        // Load config once
        self::$supported = config('i18n.supported', ['en']);
        self::$default   = config('i18n.default', 'en');

        // 1. Session (user choice)
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['app_locale'])) {
            $sessionLocale = $_SESSION['app_locale'];

            if (in_array($sessionLocale, self::$supported, true)) {
                self::$locale = $sessionLocale;
                return;
            }
        }

        // 2. Default (config)
        if (in_array(self::$default, self::$supported, true)) {
            self::$locale = self::$default;
            return;
        }

        // 3. Hard fallback (guaranteed safe)
        self::$locale = self::$supported[0] ?? 'en';
    }

    /**
     * Ensure config is loaded (used internally)
     */
    protected static function ensureLoaded(): void
    {
        if (empty(self::$supported)) {
            self::$supported = config('i18n.supported', ['en']);
            self::$default   = config('i18n.default', 'en');
        }
    }

    /**
     * Get current locale
     */
    public static function get(): string
    {
        if (self::$locale === null) {
            self::init();
        }

        return self::$locale;
    }

    /**
     * Set locale
     */
    public static function set(string $locale): void
    {
        self::ensureLoaded();

        if (!in_array($locale, self::$supported, true)) {
            return;
        }

        self::$locale = $locale;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['app_locale'] = $locale;
        }
    }

    /**
     * Clear locale (reset to default on next request)
     */
    public static function clear(): void
    {
        self::$locale = null;

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['app_locale']);
        }
    }

    /**
     * Check current locale
     */
    public static function is(string $locale): bool
    {
        return self::get() === $locale;
    }

    /**
     * Switch locale and redirect
     */
    public static function switch(string $locale): void
    {
        self::ensureLoaded();

        // Validate or fallback
        if (!in_array($locale, self::$supported, true)) {
            $locale = self::$default;
        }

        self::set($locale);

        // Prefer explicit redirect param over referer
        $redirect = $_GET['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');

        // Security: allow only internal paths
        if (!is_string($redirect) || !str_starts_with($redirect, '/')) {
            $redirect = '/';
        }

        header('Location: ' . $redirect);
        exit;
    }
}
