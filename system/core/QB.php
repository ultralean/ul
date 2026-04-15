<?php

namespace UltraLean\Core;

use UltraLean\Core\DB;

class QB
{
    protected string $table = '';
    protected ?string $model = null;

    protected array $select = ['*'];
    protected array $where = [];
    protected array $joins = [];
    protected array $bindings = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    protected ?string $locale = null;
    protected ?string $connection = null;

    // ⚡ performance layer
    protected ?array $compiled = null;
    protected bool $dirty = true;
    protected bool $translationApplied = false;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection;
    }

    public static function table(string $table): self
    {
        $qb = new self();
        $qb->table = $table;
        return $qb;
    }

    public static function model(string $modelClass): self
    {
        $qb = new self($modelClass::$connection ?? null);

        $qb->model = $modelClass;
        $qb->table = $modelClass::$table . ' ' . $modelClass::$tableAlias;

        return $qb;
    }

    // =========================
    // MUTATORS (DIRTY TRACKING)
    // =========================

    protected function markAsDirty(): void
    {
        $this->dirty = true;
        $this->compiled = null;
    }

    public function select(array|string $columns): self
    {
        $this->select = is_array($columns) ? $columns : [$columns];
        $this->markAsDirty();
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->where[] = "$column $operator ?";
        $this->bindings[] = $value;
        $this->markAsDirty();
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (!$values) {
            $this->where[] = "0=1";
            $this->markAsDirty();
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = "$column IN ($placeholders)";
        foreach ($values as $v) {
            $this->bindings[] = $v;
        }

        $this->markAsDirty();
        return $this;
    }

    public function join(string $table, string $on): self
    {
        $this->joins[] = "JOIN $table ON $on";
        $this->markAsDirty();
        return $this;
    }

    public function leftJoin(string $table, string $on): self
    {
        $this->joins[] = "LEFT JOIN $table ON $on";
        $this->markAsDirty();
        return $this;
    }

    public function orderBy(string $col, string $dir = 'ASC'): self
    {
        $this->orderBy[] = "$col $dir";
        $this->markAsDirty();
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->markAsDirty();
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        $this->markAsDirty();
        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;
        $this->markAsDirty();
        return $this;
    }

    // =========================
    // TRANSLATION (RUN ONCE)
    // =========================

    protected function applyTranslation(): void
    {
        if ($this->translationApplied) return;

        $this->translationApplied = true;

        // 🚀 ZERO OVERHEAD EXIT
        if (!config('i18n.enabled') || !$this->locale || !$this->model) {
            return;
        }

        $m = $this->model;

        if (!$m::$translationTable) return;

        $t = $m::$tableAlias;
        $tr = $m::$translationAlias;

        $this->joins[] = "LEFT JOIN {$m::$translationTable} $tr
        ON $tr.{$m::$translationForeignKey} = $t.{$m::$primaryKey}
        AND $tr.{$m::$localeColumn} = ?";

        $this->bindings[] = $this->locale;
    }

    // =========================
    // CORE COMPILER (CACHED)
    // =========================

    protected function compile(): array
    {
        if (!$this->dirty && $this->compiled !== null) {
            return $this->compiled;
        }

        $this->applyTranslation();

        $sql = "SELECT " . implode(',', $this->select) . " FROM $this->table";

        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if ($this->where) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->orderBy) {
            $sql .= ' ORDER BY ' . implode(',', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT $this->limit";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET $this->offset";
        }

        $this->compiled = [$sql, $this->bindings];
        $this->dirty = false;

        return $this->compiled;
    }

    // =========================
    // DEBUG / DEV TOOLS (NO OVERHEAD IN PROD)
    // =========================

    public function toSql(): string
    {
        return $this->compile()[0];
    }

    public function debug(bool $die = true): self
    {
        [$sql, $bindings] = $this->compile();

        echo "SQL:\n$sql\n\nBindings:\n";

        foreach ($bindings as $i => $b) {
            echo "[$i] => " . (is_scalar($b) ? $b : json_encode($b)) . "\n";
        }

        if ($die) exit;

        return $this;
    }

    public function debugRaw(bool $die = true): self
    {
        [$sql, $bindings] = $this->compile();

        foreach ($bindings as $b) {
            $val = is_numeric($b) ? $b : "'" . addslashes((string)$b) . "'";
            $sql = preg_replace('/\?/', $val, $sql, 1);
        }

        echo "Raw SQL:\n$sql\n\n";

        if ($die) exit;

        return $this;
    }

    public function explain(): array
    {
        [$sql, $bindings] = $this->compile();

        return DB::fetchAll('EXPLAIN ' . $sql, $bindings, $this->connection);
    }

    // =========================
    // EXECUTION (LEAN + FAST)
    // =========================

    public function get(): array
    {
        [$sql, $bindings] = $this->compile();
        return DB::fetchAll($sql, $bindings, $this->connection);
    }

    public function first(): ?array
    {
        $this->limit(1);
        [$sql, $bindings] = $this->compile();

        return DB::fetch($sql, $bindings, $this->connection);
    }

    public function raw(string $sql, array $bindings = []): array
    {
        return DB::fetchAll($sql, $bindings, $this->connection);
    }

    public function count(): int
    {
        $this->select = ['COUNT(*)'];
        [$sql, $bindings] = $this->compile();

        return (int) DB::scalar($sql, $bindings, $this->connection);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function cursor(callable $callback): void
    {
        [$sql, $bindings] = $this->compile();

        $stmt = DB::query($sql, $bindings, $this->connection);

        while ($row = $stmt->fetch()) {
            $callback($row);
        }
    }
}
