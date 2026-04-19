<?php

namespace UltraLean\Core;

use Throwable;
use ErrorException;

final class ErrorHandler
{
    protected static bool $isDev;
    protected static bool $isCli;
    protected static bool $logging;
    protected static bool $forceJson;

    protected static bool $booted = false;

    public static function register(): void
    {
        if (self::$booted) return;

        $env = config('app.env', 'production');

        self::$isDev  = ($env === 'development');
        self::$isCli  = (PHP_SAPI === 'cli');
        self::$forceJson = config('app.force_json', false);

        $enabled = config('logging.enabled', false);

        self::$logging = $enabled && (!self::$isDev || config('logging.enabled_in_development', false));

        // Register handlers ONCE
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$booted = true;
    }

    public static function handleException(Throwable $e): void
    {
        self::cleanBuffers();

        $isCli = self::$isCli;
        $isDev = self::$isDev;

        $headersSent = $isCli ? true : headers_sent();

        if (!$isCli && !$headersSent) {
            http_response_code(500); // avoid method_exists()
        }

        // Logging (safe)
        if (self::$logging) {
            try {
                Logger::get()->error('Error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request_id' => REQUEST_ID ?? null,
                    'url'    => $_SERVER['REQUEST_URI'] ?? null,
                    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                    'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            } catch (Throwable $logError) {
                if ($isDev) {
                    echo "<pre>Logging failed: ", $logError->getMessage(), "</pre>";
                }
            }
        }

        if ($isCli) {
            self::renderCli($e);
            return;
        }

        if (self::expectsJson()) {
            if (!$headersSent) {
                header('Content-Type: application/json');
            }

            echo json_encode(
                self::buildJson($e, $isDev),
                $isDev ? JSON_PRETTY_PRINT : 0
            );
            return;
        }

        if ($isDev) {
            echo self::renderDevHtml($e); // ✅ beautiful + fast
        } else {
            self::renderFriendlyErrorPage(
                'Something went wrong',
                'An unexpected error occurred. Please try again later.'
            );
        }
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) return false;

        if (
            $severity === E_ERROR ||
            $severity === E_PARSE ||
            $severity === E_CORE_ERROR ||
            $severity === E_COMPILE_ERROR ||
            $severity === E_USER_ERROR
        ) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if (!$error) return;

        $type = $error['type'];

        if (
            $type !== E_ERROR &&
            $type !== E_PARSE &&
            $type !== E_CORE_ERROR &&
            $type !== E_COMPILE_ERROR &&
            $type !== E_USER_ERROR
        ) {
            return;
        }

        self::handleException(new ErrorException(
            $error['message'],
            0,
            $type,
            $error['file'],
            $error['line']
        ));
    }

    protected static function expectsJson(): bool
    {
        if (self::$forceJson) return true;

        $s = $_SERVER;

        // Fast checks first
        if (!empty($s['HTTP_X_REQUESTED_WITH'])) return true;

        if (!empty($s['REQUEST_URI']) && isset($s['REQUEST_URI'][1]) && $s['REQUEST_URI'][1] === 'a') {
            if (strncmp($s['REQUEST_URI'], '/api/', 5) === 0) return true;
        }

        if (!empty($s['HTTP_ACCEPT'])) {
            return strpos($s['HTTP_ACCEPT'], 'application/json') !== false;
        }

        return false;
    }

    protected static function buildJson(Throwable $e, bool $isDev): array
    {
        if (!$isDev) {
            return [
                'error'   => true,
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        return [
            'error'   => true,
            'type'    => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ];
    }

    protected static function renderCli(Throwable $e): void
    {
        echo "[ERROR] ", get_class($e), "\n",
        $e->getMessage(), "\n",
        $e->getFile(), ":", $e->getLine(), "\n\n",
        $e->getTraceAsString(), "\n";
    }

    protected static function cleanBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * 🔥 Beautiful + ultra-light dev HTML
     */
    protected static function renderDevHtml(Throwable $e): string
    {
        $msg  = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();
        $type = htmlspecialchars(get_class($e));

        $code = self::getCodeContext($file, $line);
        $trace = htmlspecialchars($e->getTraceAsString());

        return <<<HTML
<style>
body{margin:0;background:#0f172a;color:#e2e8f0;font:14px/1.5 monospace}
.box{padding:20px}
h1{color:#f87171;margin:0 0 10px;font-size:20px}
.meta{color:#94a3b8;margin-bottom:10px}
.code{background:#020617;padding:10px;border-radius:6px}
.line{display:block;padding:2px 6px}
.hl{background:#7f1d1d}
.trace{margin-top:10px;color:#cbd5f5}
</style>
<div class="box">
<h1>{$type}</h1>
<div class="meta">{$msg}</div>
<div class="meta">{$file}:{$line}</div>
<div class="code">{$code}</div>
<pre class="trace">{$trace}</pre>
</div>
HTML;
    }

    /**
     * 🔥 Stream-based (no full file load)
     */
    protected static function getCodeContext(string $file, int $line, int $pad = 5): string
    {
        if (!is_readable($file) || filesize($file) > 200000) {
            return '[Code preview unavailable]';
        }

        $start = max($line - $pad, 1);
        $end   = $line + $pad;

        $out = '';
        $current = 0;

        $fp = fopen($file, 'r');
        if (!$fp) return '[Cannot open file]';

        while (($l = fgets($fp)) !== false) {
            $current++;
            if ($current < $start) continue;
            if ($current > $end) break;

            $code = htmlspecialchars(rtrim($l));
            $hl = ($current === $line) ? 'hl' : '';

            $out .= "<span class='line {$hl}'>{$current}: {$code}</span>";
        }

        fclose($fp);
        return $out;
    }

    protected static function renderFriendlyErrorPage(string $title, string $message): void
    {
        $requestIdDisplay = (defined('REQUEST_ID') && !empty(REQUEST_ID)) ? REQUEST_ID : 'N/A';
        echo <<<HTML
<style>
body{
    margin:0;
    background:#f8fafc;
    color:#334155;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    display:flex;
    align-items:center;
    justify-content:center;
    height:100vh
}
.box{
    text-align:center;
    max-width:420px;
    padding:30px
}
h1{
    font-size:22px;
    margin:0 0 10px;
    color:#0f172a
}
p{
    margin:0;
    color:#64748b;
    font-size:14px
}
.icon{
    font-size:40px;
    margin-bottom:15px
}
</style>

<div class="box">
    <div class="icon">⚠️</div>
    <h1>{$title}</h1>
    <p>{$message}</p>
    <p style="margin-top:10px;font-size:12px;color:#94a3b8">
Request ID: {$requestIdDisplay}
</p>
</div>
HTML;
    }
}
