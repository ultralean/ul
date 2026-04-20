<?php

namespace UltraLean\Core;

class Middleware
{
    private static array $map = [];

    private static function load(): void
    {
        if (self::$map) return;

        $cacheDir = STORAGE_PATH . '/cache';
        $cacheFile = $cacheDir . '/middleware.php';
        $hashFile  = $cacheDir . '/middleware.hash';

        $files = glob(APP_PATH . '/Middleware/*.php') ?: [];

        sort($files);

        $hashData = '';
        foreach ($files as $f) {
            $hashData .= $f . filemtime($f) . filesize($f);
        }

        $hash = md5($hashData);

        if (!is_file($cacheFile) || !is_file($hashFile) || file_get_contents($hashFile) !== $hash) {

            $map = [];

            foreach ($files as $file) {

                require_once $file;

                $class = 'App\\Middleware\\' . basename($file, '.php');

                if (class_exists($class) && method_exists($class, 'handle')) {
                    $map[basename($file, '.php')] = $class;
                }
            }

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }

            file_put_contents($cacheFile, '<?php return ' . var_export($map, true) . ';');
            file_put_contents($hashFile, $hash);
        }

        self::$map = require $cacheFile;
    }

    /* =========================
     * COMPILE PIPELINE (ZERO LOOP RUNTIME)
     * ========================= */

    public static function compileCode(array $names, string $handler): string
    {
        self::load();

        [$class, $method] = explode('@', $handler, 2);
        $fqcn = '\\App\\Controllers\\' . $class;

        // Final controller execution
        $code = "(new {$fqcn})->{$method}(...\$params)";

        foreach (array_reverse($names) as $name) {

            // 🔥 HANDLE throttle inline
            if (str_starts_with($name, 'throttle:')) {

                [$max, $sec] = array_map('intval', explode(',', explode(':', $name)[1]));

                $code = "
                if(!rate_limit(\$_SERVER['REMOTE_ADDR'] ?? 'guest', {$max}, {$sec})) {
                    \\UltraLean\\Core\\Response::error(429, 'Too Many Requests');
                }
                {$code}
            ";
                continue;
            }

            $mw = self::$map[$name] ?? null;

            if (!$mw) {
                throw new \RuntimeException("Middleware {$name} not found");
            }

            $code = "
            if((new {$mw})->handle(\\UltraLean\\Core\\Request::instance()) === false) return;
            {$code}
        ";
        }

        return $code;
    }

    public static function runtime(string $code): callable
    {
        return eval("
        return function(...\$params) {
            {$code};
        };
    ");
    }
}
