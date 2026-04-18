<?php

namespace UltraLean\Core\I18n;

class Translator
{
    protected string $locale;
    protected string $fallback;
    protected string $path;

    protected array $loaded = [];
    protected array $data = [];

    public function __construct()
    {
        $this->locale   = Locale::get();
        $this->fallback = config('i18n.fallback');
        $this->path     = rtrim(config('i18n.static.path'), '/');
    }

    public function get(string $key, array $replace = []): string
    {
        if (!config('i18n.static.enabled')) {
            return $key;
        }

        [$group, $inner] = explode('.', $key, 2) + [1 => null];

        $value =
            $this->getFrom($group, $this->locale, $inner)
            ?? $this->getFrom($group, $this->fallback, $inner)
            ?? $key;

        foreach ($replace as $k => $v) {
            $value = str_replace(":$k", $v, $value);
        }

        return $value;
    }

    protected function getFrom($group, $locale, $key)
    {
        $data = $this->load($group, $locale);

        foreach (explode('.', $key ?? '') as $seg) {
            if (!$seg || !isset($data[$seg])) return null;
            $data = $data[$seg];
        }

        return $data;
    }

    protected function load($group, $locale): array
    {
        if (isset($this->loaded[$locale][$group])) {
            return $this->data[$locale][$group];
        }

        $file = "{$this->path}/{$locale}/{$group}.php";

        $this->data[$locale][$group] =
            is_file($file) ? include $file : [];

        $this->loaded[$locale][$group] = true;

        return $this->data[$locale][$group];
    }
}
