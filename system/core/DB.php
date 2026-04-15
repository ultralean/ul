<?php

namespace UltraLean\Core;

use PDO;
use PDOException;
use UltraLean\Core\Logger;

final class DB
{
    private static array $connections = [];

    // 🔥 Cached config flags (resolved once)
    private static ?array $dbConfig = null;
    private static bool $initialized = false;
    private static bool $loggingEnabled = false;
    private static bool $logQueries = false;
    private static int $slowQueryMs = 0;

    // Optional logger cache (avoids repeated get())
    private static ?Logger $logger = null;

    private function __construct() {}

    /**
     * One-time init (zero overhead after first call)
     */
    private static function init(): void
    {
        if (self::$initialized) return;

        $app = config('app');
        $logging = config('logging');
        self::$dbConfig = config('database');
        $db = self::$dbConfig;

        $env = $app['env'] ?? 'production';

        // 🔥 Determine if logging is active
        $enabled = $logging['enabled'] ?? false;

        if ($env === 'development') {
            $enabled = $enabled && ($logging['enabled_in_development'] ?? false);
        }

        self::$loggingEnabled = $enabled;

        // 🔥 DB-specific logging flags (only meaningful if logging enabled)
        self::$logQueries = $enabled && ($db['log_queries'] ?? false);
        self::$slowQueryMs = $enabled ? (int) ($db['slow_query_ms'] ?? 0) : 0;

        self::$initialized = true;
    }

    public static function conn(?string $name = null): PDO
    {
        self::init();

        $config = self::$dbConfig;
        $name = $name ?? $config['default'];

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $conn = $config['connections'][$name] ?? null;

        if (!$conn) {
            throw new \RuntimeException("Database connection [$name] not found.");
        }

        $pdo = self::createPDO($conn);

        self::$connections[$name] = $pdo;

        return $pdo;
    }

    private static function createPDO(array $config): PDO
    {
        $dsn = self::buildDSN($config);

        $options = $config['options'] ?? [];

        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

        if (!empty($config['persistent'])) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        $attempts = $config['retry_attempts'] ?? 1;
        $delay = ($config['retry_delay_ms'] ?? 0) * 1000;

        while ($attempts--) {
            try {
                $pdo = new PDO(
                    $dsn,
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    $options
                );

                if (!empty($config['timezone'])) {
                    self::setTimezone($pdo, $config);
                }

                return $pdo;
            } catch (PDOException $e) {
                if ($attempts <= 0) {
                    throw new \RuntimeException($e->getMessage(), (int) $e->getCode());
                }

                if ($delay > 0) {
                    usleep($delay);
                }
            }
        }

        throw new \RuntimeException("Database connection failed.");
    }

    private static function buildDSN(array $c): string
    {
        return match ($c['driver']) {
            'mysql' => "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}",
            'pgsql' => "pgsql:host={$c['host']};port={$c['port']};dbname={$c['database']}",
            'sqlite' => "sqlite:{$c['database']}",
            default => throw new \RuntimeException("Unsupported driver: {$c['driver']}")
        };
    }

    private static function setTimezone(PDO $pdo, array $config): void
    {
        $tz = $config['timezone'];

        match ($config['driver']) {
            'mysql' => $pdo->exec("SET time_zone = '$tz'"),
            'pgsql' => $pdo->exec("SET TIME ZONE '$tz'"),
            default => null
        };
    }

    // =========================
    // 🔥 CORE QUERY (ULTRA-LEAN)
    // =========================

    public static function query(string $sql, array $bindings = [], ?string $conn = null): \PDOStatement
    {
        self::init();

        $pdo = self::conn($conn);

        // 🔥 FAST PATH (no logging at all)
        if (!self::$logQueries && self::$slowQueryMs <= 0) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        }

        // 🔥 Logging path (only when needed)
        $start = microtime(true);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        $time = (microtime(true) - $start) * 1000;

        $logger = self::$logger ??= Logger::get();

        if (self::$logQueries) {
            $logger->logQuery($sql, $bindings, $time);
        } elseif (self::$slowQueryMs > 0 && $time >= self::$slowQueryMs) {

            $context = [
                'sql' => $sql,
                'bindings' => $bindings,
                'time_ms' => $time
            ];

            if (!self::$logQueries && config('app.env') === 'development') {
                try {
                    $explainStmt = $pdo->prepare('EXPLAIN ' . $sql);
                    $explainStmt->execute($bindings);
                    $context['explain'] = $explainStmt->fetchAll();
                } catch (\Throwable $e) {
                    $context['explain'] = 'failed';
                }
            }

            $logger->warning('Slow query', $context);
        }

        return $stmt;
    }

    // =========================
    // HELPERS
    // =========================

    public static function fetchAll(string $sql, array $bindings = [], ?string $conn = null): array
    {
        return self::query($sql, $bindings, $conn)->fetchAll();
    }

    public static function fetch(string $sql, array $bindings = [], ?string $conn = null): ?array
    {
        $res = self::query($sql, $bindings, $conn)->fetch();
        return $res ?: null;
    }

    public static function scalar(string $sql, array $bindings = [], ?string $conn = null)
    {
        return self::query($sql, $bindings, $conn)->fetchColumn();
    }

    public static function execute(string $sql, array $bindings = [], ?string $conn = null): bool
    {
        return self::query($sql, $bindings, $conn)->rowCount() > 0;
    }

    public static function lastId(?string $conn = null): int
    {
        return (int) self::conn($conn)->lastInsertId();
    }

    // =========================
    // TRANSACTIONS
    // =========================

    public static function transaction(callable $cb, ?string $conn = null)
    {
        $pdo = self::conn($conn);

        try {
            $pdo->beginTransaction();

            $result = $cb($pdo);

            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
