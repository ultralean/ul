<?php

namespace UltraLean\Core\I18n;

use UltraLean\Core\I18n\Locale;

trait Translatable
{
    protected static string $translationTable = '';
    protected static string $translationForeignKey = '';
    protected static string $localeColumn = 'locale';

    protected static string $tableAlias = 't';
    protected static string $translationAlias = 'tr';
    protected static string $fallbackAlias = 'tf';

    protected static array $translatable = [];
    protected static bool $translationEnabled = true;

    public static function translationPayload(): ?array
    {
        if (!static::$translationEnabled) return null;
        if (!static::$translationTable || !static::$translatable) return null;

        $t  = static::$tableAlias;
        $tr = static::$translationAlias;
        $tf = static::$fallbackAlias;

        $locale   = Locale::get();
        $fallback = config('i18n.fallback', 'en');

        return [
            'joins' => [
                "LEFT JOIN " . static::$translationTable . " $tr
                 ON $tr." . static::$translationForeignKey . " = $t." . static::$primaryKey . "
                AND $tr." . static::$localeColumn . " = ?",

                "LEFT JOIN " . static::$translationTable . " $tf
                 ON $tf." . static::$translationForeignKey . " = $t." . static::$primaryKey . "
                AND $tf." . static::$localeColumn . " = ?",
            ],

            'bindings' => [$locale, $fallback],

            'select' => array_map(
                fn($col) => "COALESCE($tr.$col, $tf.$col) AS $col",
                static::$translatable
            )
        ];
    }
}
