<?php

namespace UltraLean\Core;

use PDO;
use UltraLean\Core\DB;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static ?string $connection = null;

    // timestamps (optional)
    protected static bool $timestamps = false;
    protected static string $createdAt = 'created_at';
    protected static string $updatedAt = 'updated_at';

    // multilingual (optional)
    protected static ?string $translationTable = null;
    protected static string $translationForeignKey = '';
    protected static string $localeColumn = 'locale';
    protected static string $tableAlias = 't';
    protected static string $translationAlias = 'tr';

    // ⚡ caches
    protected static ?string $findSql = null;
    protected static ?string $deleteSql = null;
    protected static ?string $now = null;

    // =========================
    // ⚡ CORE DB ACCESS
    // =========================

    protected static function db(): PDO
    {
        return DB::conn(static::$connection);
    }

    // =========================
    // 🔥 BASIC CRUD
    // =========================

    public static function find(int|string $id, array|string $columns = '*'): ?array
    {
        // ⚡ FAST PATH (cached SQL)
        if ($columns === '*' || $columns === ['*']) {
            $sql = static::$findSql ??=
                "SELECT * FROM " . static::$table .
                " WHERE " . static::$primaryKey . " = ? LIMIT 1";

            return DB::fetch($sql, [$id], static::$connection);
        }

        // ⚡ dynamic columns (no cache)
        $cols = is_array($columns) ? implode(',', $columns) : $columns;

        $sql = "SELECT $cols FROM " . static::$table .
            " WHERE " . static::$primaryKey . " = ? LIMIT 1";

        return DB::fetch($sql, [$id], static::$connection);
    }

    public static function findMany(array $ids, array|string $columns = '*'): array
    {
        if (empty($ids)) return [];

        $cols = ($columns === '*' || $columns === ['*'])
            ? '*'
            : (is_array($columns) ? implode(',', $columns) : $columns);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT $cols FROM " . static::$table .
            " WHERE " . static::$primaryKey . " IN ($placeholders)";

        return DB::fetchAll($sql, $ids, static::$connection);
    }

    public static function all(int $limit = 1000, array|string $columns = '*'): array
    {
        $cols = ($columns === '*' || $columns === ['*'])
            ? '*'
            : (is_array($columns) ? implode(',', $columns) : $columns);

        $sql = "SELECT $cols FROM " . static::$table . " LIMIT $limit";

        return DB::fetchAll($sql, [], static::$connection);
    }

    public static function insert(array $data): int
    {
        $data = static::applyTimestamps($data, true);

        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO " . static::$table . " ($fields) VALUES ($placeholders)";

        DB::execute($sql, array_values($data), static::$connection);

        return DB::lastId(static::$connection);
    }

    public static function insertMany(array $rows): bool
    {
        if (empty($rows)) return false;

        $keys = array_keys($rows[0]);
        $fields = implode(',', $keys);

        $placeholdersRow = '(' . implode(',', array_fill(0, count($keys), '?')) . ')';
        $placeholders = implode(',', array_fill(0, count($rows), $placeholdersRow));

        $bindings = [];

        foreach ($rows as $row) {
            $row = static::applyTimestamps($row, true);

            foreach ($row as $value) {
                $bindings[] = $value;
            }
        }

        $sql = "INSERT INTO " . static::$table . " ($fields) VALUES $placeholders";

        return DB::execute($sql, $bindings, static::$connection);
    }

    public static function update(int|string $id, array $data): bool
    {
        $data = static::applyTimestamps($data, false);

        $setParts = [];
        foreach ($data as $k => $_) {
            $setParts[] = "$k = ?";
        }
        $set = implode(',', $setParts);

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
        $cols = ($columns === '*' || $columns === ['*'])
            ? '*'
            : (is_array($columns) ? implode(',', $columns) : $columns);

        $sql = "SELECT $cols FROM " . static::$table . " WHERE $column = ?";

        return DB::fetchAll($sql, [$value], static::$connection);
    }

    public static function firstWhere(string $column, $value, array|string $columns = '*'): ?array
    {
        $cols = ($columns === '*' || $columns === ['*'])
            ? '*'
            : (is_array($columns) ? implode(',', $columns) : $columns);

        $sql = "SELECT $cols FROM " . static::$table .
            " WHERE $column = ? LIMIT 1";

        return DB::fetch($sql, [$value], static::$connection);
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
            "SELECT EXISTS(SELECT 1 FROM " . static::$table . " WHERE $column = ?)",
            [$value],
            static::$connection
        );
    }

    // =========================
    // 🌍 MULTI-LANGUAGE
    // =========================

    public static function findWithLocale(int|string $id, string $locale): ?array
    {
        if (!config('i18n.enabled') || !static::$translationTable) {
            return static::find($id);
        }

        $sql = "
            SELECT t.*, tr.*
            FROM " . static::$table . " t
            LEFT JOIN " . static::$translationTable . " tr
                ON tr." . static::$translationForeignKey . " = t." . static::$primaryKey . "
                AND tr." . static::$localeColumn . " = ?
            WHERE t." . static::$primaryKey . " = ?
            LIMIT 1
        ";

        return DB::fetch($sql, [$locale, $id], static::$connection);
    }

    public static function insertWithTranslations(array $data, array $translations): int
    {
        return DB::transaction(function () use ($data, $translations) {

            $id = static::insert($data);

            if (config('i18n.enabled') && static::$translationTable) {
                foreach ($translations as $locale => $row) {

                    $row[static::$translationForeignKey] = $id;
                    $row[static::$localeColumn] = $locale;

                    DB::execute(
                        static::buildInsertSQL(static::$translationTable, $row),
                        array_values($row),
                        static::$connection
                    );
                }
            }

            return $id;
        }, static::$connection);
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

    protected static function buildInsertSQL(string $table, array $data): string
    {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        return "INSERT INTO $table ($fields) VALUES ($placeholders)";
    }

    public static function resetNow(): void
    {
        static::$now = null;
    }
}
