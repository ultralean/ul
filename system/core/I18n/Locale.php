<?php

namespace UltraLean\Core\I18n;

class Locale
{
    protected static ?string $currentLocale = null;

    /**
     * Set locale manually
     */
    public static function set(string $locale): void
    {
        if (!self::isSupported($locale)) {
            return;
        }

        self::$currentLocale = $locale;

        if (config('i18n.resolver.session')) {
            $_SESSION['app_locale'] = $locale;
        }
    }

    /**
     * Resolve locale (FAST + deterministic)
     */
    public static function get(): string
    {
        if (!config('i18n.enabled')) {
            return config('i18n.default', 'en');
        }

        if (self::$currentLocale) {
            return self::$currentLocale;
        }

        // 1. Session (fast)
        if (config('i18n.resolver.session') && !empty($_SESSION['app_locale'])) {
            $locale = $_SESSION['app_locale'];

            if (self::isSupported($locale)) {
                return self::$currentLocale = $locale;
            }
        }

        // 2. Default (NO DB CALL)
        return self::$currentLocale = config('i18n.default', 'en');
    }

    /**
     * Validate supported locales (O(1))
     */
    protected static function isSupported(string $locale): bool
    {
        static $map = null;

        if ($map === null) {
            $map = array_flip(config('i18n.supported', ['en']));
        }

        return isset($map[$locale]);
    }

    /**
     * Reset (rare use)
     */
    public static function clear(): void
    {
        self::$currentLocale = null;
        unset($_SESSION['app_locale']);
    }
}
