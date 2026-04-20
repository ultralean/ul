<?php

namespace UltraLean\Core;

final class AutoLoader
{
    private string $corePath;
    private string $appPath;

    public function __construct()
    {
        $this->corePath = rtrim(SYSTEM_PATH, '/') . '/core/';
        $this->appPath  = rtrim(APP_PATH, '/') . '/';

        spl_autoload_register([$this, 'loadClass'], true, true);
    }

    private function loadClass(string $class): void
    {
        if (strncmp($class, 'UltraLean\\Core\\', 15) === 0) {
            require $this->corePath . strtr(substr($class, 15), '\\', '/') . '.php';
            return;
        }

        if (strncmp($class, 'App\\', 4) === 0) {
            require $this->appPath . strtr(substr($class, 4), '\\', '/') . '.php';
        }
    }
}
