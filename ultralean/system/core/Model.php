<?php

namespace UltraLean\Core;

use PDO;
use UltraLean\Core\I18n\Translatable;

abstract class Model
{
    use Translatable;

    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static ?string $connection = null;

    // timestamps (optional)
    protected static bool $timestamps = false;
    protected static string $createdAt = 'created_at';
    protected static string $updatedAt = 'updated_at';

    // ⚡ SQL cache
    protected static ?string $findSql = null;
    protected static ?string $deleteSql = null;
    protected static array $findManySqlCache = [];

    protected static ?string $now = null;

    // =========================
    // ⚡ CORE DB ACCESS
    // =========================

    protected static function db(): PDO
    {
        return DB::conn(static::$connection);
    }

    protected static function buildTranslatedSelect(string $baseSql, array $bindings = []): array
    {
        if (
            !config('i18n.database.enabled') ||
            !in_array(\UltraLean\Core\I18n\Translatable::class, class_uses(static::class))
        ) {
            return [$baseSql, $bindings];
        }

        $payload = static::translationPayload();

        if (!$payload) {
            return [$baseSql, $bindings];
        }

        $t = static::$tableAlias;

        // replace SELECT *
        $select = "$t.*," . implode(',', $payload['select']);
        $sql = str_replace('SELECT *', "SELECT $select", $baseSql);

        // add alias
        $sql = str_replace(
            'FROM ' . static::$table,
            'FROM ' . static::$table . " $t",
            $sql
        );

        // append joins
        $sql .= ' ' . implode(' ', $payload['joins']);

        // prepend bindings
        $bindings = array_merge($payload['bindings'], $bindings);

        return [$sql, $bindings];
    }

    // =========================
    // 🔥 BASIC CRUD
    // =========================

    public static function find(int|string $id, array|string $columns = '*'): ?array
    {
        $sql = "SELECT * FROM " . static::$table .
            " WHERE " . static::$primaryKey . " = ? LIMIT 1";

        [$sql, $bindings] = static::buildTranslatedSelect($sql, [$id]);

        return DB::fetch($sql, $bindings, static::$connection);
    }

    public static function findMany(array $ids, array|string $columns = '*'): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT * FROM " . static::$table .
            " WHERE " . static::$primaryKey . " IN ($placeholders)";

        [$sql, $bindings] = static::buildTranslatedSelect($sql, $ids);

        return DB::fetchAll($sql, $bindings, static::$connection);
    }

    public static function all(int $limit = 1000, array|string $columns = '*'): array
    {
        $sql = "SELECT * FROM " . static::$table . " LIMIT $limit";

        [$sql, $bindings] = static::buildTranslatedSelect($sql);

        return DB::fetchAll($sql, $bindings, static::$connection);
    }

    public static function insert(array $data): int
    {
        $data = static::applyTimestamps($data, true);

        $keys = array_keys($data);

        $sql = "INSERT INTO " . static::$table .
            " (" . implode(',', $keys) . ") VALUES (" .
            implode(',', array_fill(0, count($keys), '?')) . ")";

        DB::execute($sql, array_values($data), static::$connection);

        return DB::lastId(static::$connection);
    }

    public static function insertMany(array $rows): bool
    {
        if (empty($rows)) return false;

        $keys = array_keys($rows[0]);
        $fieldCount = count($keys);

        $rowPlaceholder = '(' . implode(',', array_fill(0, $fieldCount, '?')) . ')';
        $placeholders = implode(',', array_fill(0, count($rows), $rowPlaceholder));

        $bindings = [];

        foreach ($rows as $row) {
            $row = static::applyTimestamps($row, true);

            foreach ($keys as $key) {
                $bindings[] = $row[$key] ?? null;
            }
        }

        $sql = "INSERT INTO " . static::$table .
            " (" . implode(',', $keys) . ") VALUES $placeholders";

        return DB::execute($sql, $bindings, static::$connection);
    }

    public static function update(int|string $id, array $data): bool
    {
        if (empty($data)) return true;

        $data = static::applyTimestamps($data, false);

        $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));

        $sql = "UPDATE " . static::$table .
            " SET $set WHERE " . static::$primaryKey . " = ?";

        return DB::execute(
            $sql,
            [...array_values($data), $id],
            static::$connection
        );
    }

    public static function delete(int|string $id): bool
    {
        $sql = static::$deleteSql ??=
            "DELETE FROM " . static::$table .
            " WHERE " . static::$primaryKey . " = ?";

        return DB::execute($sql, [$id], static::$connection);
    }

    // =========================
    // ⚡ SIMPLE WHERE HELPERS
    // =========================

    public static function where(string $column, $value, array|string $columns = '*'): array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE $column = ?";

        [$sql, $bindings] = static::buildTranslatedSelect($sql, [$value]);

        return DB::fetchAll($sql, $bindings, static::$connection);
    }

    public static function firstWhere(string $column, $value, array|string $columns = '*'): ?array
    {
        $sql = "SELECT * FROM " . static::$table .
            " WHERE $column = ? LIMIT 1";

        [$sql, $bindings] = static::buildTranslatedSelect($sql, [$value]);

        return DB::fetch($sql, $bindings, static::$connection);
    }

    public static function count(string $column = '*'): int
    {
        return (int) DB::scalar(
            "SELECT COUNT($column) FROM " . static::$table,
            [],
            static::$connection
        );
    }

    public static function exists(string $column, $value): bool
    {
        return (bool) DB::scalar(
            "SELECT EXISTS(
                SELECT 1 FROM " . static::$table . " WHERE $column = ?
            )",
            [$value],
            static::$connection
        );
    }

    // =========================
    // 🔗 QUERY BUILDER ENTRY
    // =========================

    public static function query(): QB
    {
        return QB::model(static::class);
    }

    // =========================
    // ⚡ INTERNAL HELPERS
    // =========================

    protected static function applyTimestamps(array $data, bool $isInsert): array
    {
        if (!static::$timestamps) return $data;

        $now = static::$now ??= date('Y-m-d H:i:s');

        if ($isInsert && !isset($data[static::$createdAt])) {
            $data[static::$createdAt] = $now;
        }

        if (!isset($data[static::$updatedAt])) {
            $data[static::$updatedAt] = $now;
        }

        return $data;
    }

    public static function resetNow(): void
    {
        static::$now = null;
    }
}
