# UltraLean PHP Database Layer Documentation

## Overview

UltraLean provides a **high-performance, low-overhead database layer** built on top of PDO. It consists of three main components:

* **DB** → Raw database access (fastest layer)
* **QB (Query Builder)** → Fluent query construction
* **Model** → Structured data access with optional features

### Design Philosophy

* ⚡ Near raw PDO performance
* 🧩 Optional abstraction (not enforced)
* 🚫 No ORM overhead
* 🔌 Multi-database support
* 🌍 Optional multilingual support

---

# 1. DB Class (Core Database Layer)

## Purpose

Provides **direct, fast, and minimal abstraction** over PDO with helper utilities.

---

## Connection

### `DB::conn(?string $name = null): PDO`

Returns a cached PDO connection.

```php
DB::conn(); // default connection
DB::conn('pgsql1'); // specific connection
```

---

## Raw Query Execution

### `DB::query(string $sql, array $bindings = [], ?string $conn = null): PDOStatement`

Execute a prepared query.

```php
$stmt = DB::query("SELECT * FROM users WHERE id = ?", [1]);
```

---

### `DB::fetchAll(string $sql, array $bindings = [], ?string $conn = null): array`

Fetch all rows.

```php
$users = DB::fetchAll("SELECT * FROM users");
```

---

### `DB::fetch(string $sql, array $bindings = [], ?string $conn = null): ?array`

Fetch a single row.

```php
$user = DB::fetch("SELECT * FROM users WHERE id = ?", [1]);
```

---

### `DB::scalar(string $sql, array $bindings = [], ?string $conn = null)`

Fetch single value.

```php
$count = DB::scalar("SELECT COUNT(*) FROM users");
```

---

### `DB::execute(string $sql, array $bindings = [], ?string $conn = null): bool`

Execute insert/update/delete.

```php
DB::execute("UPDATE users SET status = ? WHERE id = ?", ['active', 1]);
```

---

### `DB::lastId(?string $conn = null): int`

Get last inserted ID.

```php
$id = DB::lastId();
```

---

## Transactions

### `DB::transaction(callable $callback, ?string $conn = null)`

```php
DB::transaction(function () {
    DB::execute("UPDATE accounts SET balance = balance - 100 WHERE id = ?", [1]);
    DB::execute("UPDATE accounts SET balance = balance + 100 WHERE id = ?", [2]);
});
```

---

# 2. QB (Query Builder)

## Purpose

Provides a **lightweight fluent interface** for building SQL queries.

---

## Creating Queries

### `QB::table(string $table): QB`

```php
QB::table('users')->get();
```

---

### `QB::model(string $modelClass): QB`

```php
User::query()->get();
```

---

## Select

### `select(array|string $columns): QB`

```php
QB::table('users')->select(['id', 'name'])->get();
```

---

## Where

### `where(string $column, string $operator, $value): QB`

```php
QB::table('users')->where('status', '=', 'active')->get();
```

---

### `whereIn(string $column, array $values): QB`

```php
QB::table('users')->whereIn('id', [1,2,3])->get();
```

---

## Joins

### `join(string $table, string $on): QB`

```php
->join('posts', 'posts.user_id = users.id')
```

---

### `leftJoin(string $table, string $on): QB`

```php
->leftJoin('posts', 'posts.user_id = users.id')
```

---

## Ordering & Limits

### `orderBy(string $column, string $direction = 'ASC'): QB`

```php
->orderBy('id', 'DESC')
```

---

### `limit(int $limit): QB`

```php
->limit(10)
```

---

### `offset(int $offset): QB`

```php
->offset(20)
```

---

## Multilingual Support

### `locale(string $locale): QB`

```php
Post::query()->locale('en')->get();
```

---

## Fetching

### `get(): array`

```php
$rows = QB::table('users')->get();
```

---

### `first(): ?array`

```php
$user = QB::table('users')->first();
```

---

## Insert

### `insert(array $data): int`

```php
$id = QB::table('users')->insert([
    'name' => 'John'
]);
```

---

## Update

### `update(array $data): bool`

```php
QB::table('users')->where('id', '=', 1)->update([
    'name' => 'Updated'
]);
```

---

## Delete

### `delete(): bool`

```php
QB::table('users')->where('id', '=', 1)->delete();
```

---

# 3. Model Class

## Purpose

Provides **structured database interaction** with optional features.

---

## Basic Usage

```php
class User extends Model
{
    protected static string $table = 'users';
}
```

---

## CRUD

### `find($id): ?array`

```php
$user = User::find(1);
```

---

### `findMany(array $ids): array`

```php
$users = User::findMany([1,2,3]);
```

---

### `all(int $limit = 1000): array`

```php
$users = User::all();
```

---

### `insert(array $data): int`

```php
$id = User::insert(['name' => 'John']);
```

---

### `insertMany(array $rows): bool`

```php
User::insertMany([
    ['name' => 'A'],
    ['name' => 'B']
]);
```

---

### `update($id, array $data): bool`

```php
User::update(1, ['name' => 'Updated']);
```

---

### `delete($id): bool`

```php
User::delete(1);
```

---

## Where Helpers

### `where(string $column, $value): array`

```php
User::where('status', 'active');
```

---

### `firstWhere(string $column, $value): ?array`

```php
User::firstWhere('email', $email);
```

---

### `count(string $column = '*'): int`

```php
User::count();
```

---

### `exists(string $column, $value): bool`

```php
User::exists('email', $email);
```

⚡ How You Use It
Default (fast path, cached SQL)
User::find(1);

✔ Uses cached SQL
✔ Uses SELECT *
✔ Fastest possible

Custom columns (optimized data usage)
User::find(1, ['id', 'name']);

✔ Smaller result
✔ Less DB load
✔ Slightly more CPU (no cache) — acceptable tradeoff

Multiple rows
User::findMany([1,2,3], ['id', 'email']);
Where
User::where('status', 'active', ['id', 'name']);
🧠 Why this is optimal
Fast path:
User::find(1);
cached SQL
no implode
no extra logic

👉 maximum speed

Flexible path:
User::find(1, ['id', 'name']);
minimal overhead
big DB performance gain

👉 real-world faster

---

## Query Builder Access

### `query(): QB`

```php
User::query()->where('status', '=', 'active')->get();
```

---

## Multilingual Support

### Setup

```php
class Post extends Model
{
    protected static string $table = 'posts';

    protected static ?string $translationTable = 'post_translations';
    protected static string $translationForeignKey = 'post_id';
}
```

---

### `findWithLocale($id, string $locale): ?array`

```php
$post = Post::findWithLocale(1, 'en');
```

---

### `insertWithTranslations(array $data, array $translations): int`

```php
Post::insertWithTranslations(
    ['slug' => 'test'],
    [
        'en' => ['title' => 'Hello'],
        'ur' => ['title' => 'سلام']
    ]
);
```

---

## Timestamps

### Enable timestamps

```php
protected static bool $timestamps = true;
```

Fields:

* `created_at`
* `updated_at`

---

## Multiple Database Connections

### Define connection

```php
protected static ?string $connection = 'pgsql1';
```

compile()
toSql()
toSqlWithBindings()   (optional)
debug()
debugRaw()
explain()             (optional but powerful)

⚡ USAGE EXAMPLES
1. Get SQL only
$sql = User::query()->where('id', 1)->toSql();
2. Get SQL + bindings
[$sql, $bindings] = User::query()->where('id', 1)->toSqlWithBindings();
3. Debug query
User::query()->where('id', 1)->debug();
4. Analyze performance
User::query()->where('id', 1)->explain();


---

# Performance Notes

* ⚡ DB → ~100% raw PDO speed
* ⚡ QB → ~97–99%
* ⚡ Model → ~97–99%
* 🚫 No ORM overhead
* 🚫 No reflection
* 🚫 No dynamic schema parsing

---

# Best Practices

✅ Use DB for complex queries
✅ Use QB for readable queries
✅ Use Model for structured access

❌ Avoid adding ORM features
❌ Avoid auto-loading relationships
❌ Avoid magic methods

---

# Final Architecture

```
DB (Raw SQL)
   ↓
QB (Fluent Builder)
   ↓
Model (Convenience Layer)
```

---

# Conclusion

UltraLean provides:

* High performance
* Clean structure
* Full flexibility
* Zero unnecessary overhead

You get **the speed of raw PHP** with **the convenience of a framework**.

---
