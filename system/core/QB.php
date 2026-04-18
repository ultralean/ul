<?php

namespace UltraLean\Core;

use UltraLean\Core\DB;
use UltraLean\Core\I18n\Translatable;
use UltraLean\Core\I18n\Locale;

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

    protected ?string $connection = null;

    // ⚡ performance layer
    protected ?array $compiled = null;
    protected bool $dirty = true;
    protected bool $translationApplied = false;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection;
    }

    // =========================
    // FACTORY
    // =========================

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
        $qb->table = $modelClass::$table . ' t';

        return $qb;
    }

    // =========================
    // DIRTY TRACKING
    // =========================

    protected function markAsDirty(): void
    {
        $this->dirty = true;
        $this->compiled = null;
    }

    // =========================
    // SELECT / FROM
    // =========================

    public function select(array|string $columns): self
    {
        $this->select = is_array($columns) ? $columns : [$columns];
        $this->markAsDirty();
        return $this;
    }

    public function selectRaw(string $sql): self
    {
        $this->select = [$sql];
        $this->markAsDirty();
        return $this;
    }

    public function from(string $table): self
    {
        $this->table = $table;
        $this->markAsDirty();
        return $this;
    }

    // =========================
    // WHERE
    // =========================

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

    // =========================
    // JOINS
    // =========================

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

    public function joinRaw(string $sql, array $bindings = []): self
    {
        $this->joins[] = $sql;

        foreach ($bindings as $b) {
            $this->bindings[] = $b;
        }

        $this->markAsDirty();
        return $this;
    }

    // =========================
    // ORDER / LIMIT / OFFSET
    // =========================

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

    // =========================
    // TRANSLATION HOOK (NO LOGIC HERE)
    // =========================
    protected function applyTranslation(): void
    {
        if ($this->translationApplied) return;

        $this->translationApplied = true;

        if (
            !$this->model ||
            !config('i18n.database.enabled') ||
            !in_array(\UltraLean\Core\I18n\Translatable::class, class_uses($this->model))
        ) {
            return;
        }

        $payload = $this->model::translationPayload();

        if (!$payload) return;

        // joins
        foreach ($payload['joins'] as $join) {
            $this->joins[] = $join;
        }

        // bindings
        foreach ($payload['bindings'] as $b) {
            $this->bindings[] = $b;
        }

        // select
        if ($this->select === ['*']) {
            $this->select = [$this->table . '.*'];
        }

        $this->select = array_merge($this->select, $payload['select']);
    }

    // =========================
    // COMPILER
    // =========================

    protected function compile(): array
    {
        if (!$this->dirty && $this->compiled !== null) {
            return $this->compiled;
        }

        $this->applyTranslation();

        $sql = "SELECT " . implode(',', $this->select)
            . " FROM " . $this->table;

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
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        $this->compiled = [$sql, $this->bindings];
        $this->dirty = false;

        return $this->compiled;
    }

    // =========================
    // EXECUTION
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

    public function raw(string $sql, array $bindings = []): array
    {
        return DB::fetchAll($sql, $bindings, $this->connection);
    }

    // =========================
    // DEBUG (no production cost)
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

        return DB::fetchAll(
            'EXPLAIN ' . $sql,
            $bindings,
            $this->connection
        );
    }
}
