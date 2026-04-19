<?php

namespace UltraLean\Core;

class AutoLoader
{
    private array $prefixes;

    public function __construct()
    {
        $this->prefixes = [
            'UltraLean\\Core\\' => rtrim(SYSTEM_PATH, '/') . '/core/',
            'App\\'       => rtrim(APP_PATH, '/') . '/',
        ];
        spl_autoload_register([$this, 'loadClass'], true, true);
    }

    private function loadClass(string $class): void
    {
        foreach ($this->prefixes as $prefix => $baseDir) {
            // Fast prefix check
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            // Remove namespace prefix
            $relativeClass = substr($class, strlen($prefix));

            // Convert namespace to path
            $file = $baseDir . strtr($relativeClass, '\\', '/') . '.php';

            // Direct require (fast path)
            require $file;

            return;
        }
    }
}
