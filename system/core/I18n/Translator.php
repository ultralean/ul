<?php

namespace UltraLean\Core\I18n;

class Translator
{
    protected string $locale;
    protected string $fallback;
    protected string $langPath;

    protected array $loaded = [];
    protected array $translations = [];

    public function __construct()
    {
        $this->locale   = Locale::get();
        $this->fallback = config('i18n.fallback', 'en');
        $this->langPath = rtrim(config('i18n.path'), '/');
    }

    public function get(string $key, array $replacements = []): string
    {
        if (!config('i18n.enabled')) {
            return $key;
        }

        [$group, $nestedKey] = $this->parseKey($key);

        $value =
            $this->arrayGet($this->loadGroup($group, $this->locale), $nestedKey)
            ?? $this->arrayGet($this->loadGroup($group, $this->fallback), $nestedKey)
            ?? $key;

        return $this->applyReplacements($value, $replacements);
    }

    protected function loadGroup(string $group, string $locale): array
    {
        if (isset($this->loaded[$locale][$group])) {
            return $this->translations[$locale][$group];
        }

        $file = "{$this->langPath}/{$locale}/{$group}.php";

        $this->translations[$locale][$group] =
            is_file($file) ? include $file : [];

        $this->loaded[$locale][$group] = true;

        return $this->translations[$locale][$group];
    }

    protected function parseKey(string $key): array
    {
        $parts = explode('.', $key, 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    protected function arrayGet(array $array, string $key)
    {
        if (!$key) return null;

        foreach (explode('.', $key) as $segment) {
            if (!isset($array[$segment])) {
                return null;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    protected function applyReplacements(string $text, array $replacements): string
    {
        foreach ($replacements as $k => $v) {
            $text = str_replace(":$k", $v, $text);
        }
        return $text;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
