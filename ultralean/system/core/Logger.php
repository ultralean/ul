<?php

namespace UltraLean\Core;

final class Logger
{
    private static ?self $instance = null;

    private bool $enabled;
    private int $level;
    private $handle;
    private bool $isDev;
    private array $buffer = [];

    private const FLUSH_THRESHOLD = 50;

    // Time cache
    private int $lastTime = 0;
    private string $cachedTime = '';

    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7
    ];

    private const LEVEL_NAMES = [
        0 => 'DEBUG',
        1 => 'INFO',
        2 => 'NOTICE',
        3 => 'WARNING',
        4 => 'ERROR',
        5 => 'CRITICAL',
        6 => 'ALERT',
        7 => 'EMERGENCY'
    ];

    public function __construct()
    {
        $env = config('app.env') ?? 'production';
        $logging = config('logging') ?? [];

        $this->isDev = $env === 'development';
        $this->enabled = $logging['enabled'] ?? false;

        if ($this->isDev) {
            $this->enabled = $this->enabled && ($logging['enabled_in_development'] ?? false);
        }

        if (!$this->enabled) return;

        $levelName = $this->isDev
            ? ($logging['level_in_development'] ?? 'debug')
            : ($logging['level'] ?? 'error');

        $this->level = self::LEVELS[strtolower($levelName)] ?? 0;

        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file = $dir . '/app-' . APP . '-' . date('Y-m-d') . '.log';
        $this->handle = fopen($file, 'ab');

        // Optional: disable stream buffering (hardcore mode)
        // stream_set_write_buffer($this->handle, 0);

        // Ensure flush even on fatal errors
        register_shutdown_function([$this, 'flush']);
    }

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Cached timestamp (per second)
     */
    private function now(): string
    {
        $t = time();
        if ($t !== $this->lastTime) {
            $this->lastTime = $t;
            $this->cachedTime = date('Y-m-d H:i:s', $t);
        }
        return $this->cachedTime;
    }

    public function log(int $level, string|\Throwable $message, array $context = []): void
    {
        if (!$this->enabled || $level < $this->level) return;

        // Exception extraction (fast path)
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $e = $context['exception'];
            $message = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            unset($context['exception']);
        } elseif ($message instanceof \Throwable) {
            $message = $message->getMessage() . ' in ' . $message->getFile() . ':' . $message->getLine();
        }

        // Build line (fast concatenation)
        $line = '[' . $this->now() . '] '
            . self::LEVEL_NAMES[$level]
            . ': '
            . $message;

        // Context (optimized)
        if ($context) {
            $ctx = $this->formatContext($context);
            if ($ctx !== '') {
                $line .= ' | ' . $ctx;
            }
        }

        $this->buffer[] = $line . "\n";
        $this->flushIfNeeded();
    }

    public function logQuery(string $sql, array $bindings, float $timeMs): void
    {
        if (!$this->enabled) return;

        // Keep it lightweight (important for performance)
        $this->log(self::LEVELS['debug'], 'DB Query', [
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => round($timeMs, 2)
        ]);
    }

    public function exception(\Throwable $e, array $context = []): void
    {
        if (!$this->enabled) return;

        $line = '[' . $this->now() . '] ERROR: '
            . get_class($e) . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine();

        if ($context) {
            $parts = [];

            foreach ($context as $k => $v) {
                // Fastest checks first
                if (is_scalar($v)) {
                    $parts[] = "$k=$v";
                } elseif ($v instanceof \Throwable) {
                    continue;
                } elseif (is_array($v)) {
                    $parts[] = "$k=" . (count($v) < 5 ? json_encode($v) : '[array]');
                } else {
                    $parts[] = "$k=[object]";
                }
            }

            if ($parts) {
                $line .= ' | ' . implode(' ', $parts);
            }
        }

        $line .= "\n";

        if ($this->isDev) {
            $line .= $e->getTraceAsString() . "\n";
        }

        $this->buffer[] = $line;
        $this->flushIfNeeded();
    }

    private function flushIfNeeded(): void
    {
        if (!$this->handle || !$this->buffer) return;

        if (count($this->buffer) >= self::FLUSH_THRESHOLD) {
            fwrite($this->handle, implode('', $this->buffer));
            $this->buffer = [];
        }
    }

    private function formatContext(array $context): string
    {
        $parts = [];

        foreach ($context as $key => $value) {
            // Fastest checks first
            if (is_scalar($value)) {
                $parts[] = "$key=$value";
            } elseif ($value instanceof \Throwable) {
                continue;
            } elseif (is_array($value)) {
                $parts[] = "$key=" . (count($value) < 5 ? json_encode($value) : '[array]');
            } else {
                $parts[] = "$key=[object]";
            }
        }

        return $parts ? implode(' ', $parts) : '';
    }

    public function flush(): void
    {
        if (!$this->handle || !$this->buffer) return;

        fwrite($this->handle, implode('', $this->buffer));
        $this->buffer = [];
    }

    // Thin wrappers (zero overhead inline calls)
    public function debug(string $m, array $c = []): void
    {
        $this->log(0, $m, $c);
    }
    public function info(string $m, array $c = []): void
    {
        $this->log(1, $m, $c);
    }
    public function notice(string $m, array $c = []): void
    {
        $this->log(2, $m, $c);
    }
    public function warning(string $m, array $c = []): void
    {
        $this->log(3, $m, $c);
    }
    public function error(string $m, array $c = []): void
    {
        $this->log(4, $m, $c);
    }
    public function critical(string $m, array $c = []): void
    {
        $this->log(5, $m, $c);
    }
    public function alert(string $m, array $c = []): void
    {
        $this->log(6, $m, $c);
    }
    public function emergency(string $m, array $c = []): void
    {
        $this->log(7, $m, $c);
    }

    public function __destruct()
    {
        $this->flush();
        if ($this->handle) fclose($this->handle);
    }
}
