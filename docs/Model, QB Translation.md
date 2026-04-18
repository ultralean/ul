# UltraLean Database & I18n Documentation

## Overview

UltraLean provides a lightweight, high-performance database layer with:

* ⚡ Direct PDO-based execution (`DB`)
* 🧠 Static ORM-like layer (`Model`)
* 🔗 Fluent query builder (`QB`)
* 🌍 Automatic database-driven translations (`Translatable`)

All components are designed for **zero overhead**, **maximum performance**, and **clean architecture**.

---

# 🌍 Translatable Trait

## Purpose

The `Translatable` trait enables **automatic multilingual support** using a separate translation table.

It works seamlessly with both:

* `Model`
* `Query Builder (QB)`

No manual locale handling is required.

---

## ✅ How It Works

* Automatically joins translation table
* Selects translated columns using `COALESCE`
* Falls back to default locale if translation not found

---

## 🧩 Required Properties

```php
protected static string $translationTable = 'post_translations';
protected static string $translationForeignKey = 'post_id';
protected static array $translatable = ['title', 'content'];
```

---

## ⚙️ Optional Configuration

```php
protected static string $localeColumn = 'locale';
protected static string $tableAlias = 't';
protected static string $translationAlias = 'tr';
protected static string $fallbackAlias = 'tf';
protected static bool $translationEnabled = true;
```

---

## 🗄️ Database Structure

### Base Table (`posts`)

| id  | slug |
| --- | ---- |

### Translation Table (`post_translations`)

| id  | post_id | locale | title | content |
| --- | ------- | ------ | ----- | ------- |

---

## 🌐 Locale Handling

Locale is automatically resolved via:

```php
Locale::get();
```

Fallback locale:

```php
config('i18n.fallback', 'en');
```

---

## ⚡ What Happens Internally

Generated SQL:

```sql
SELECT t.*,
       COALESCE(tr.title, tf.title) AS title,
       COALESCE(tr.content, tf.content) AS content
FROM posts t
LEFT JOIN post_translations tr
  ON tr.post_id = t.id AND tr.locale = ?
LEFT JOIN post_translations tf
  ON tf.post_id = t.id AND tf.locale = ?
```

Bindings:

```
[current_locale, fallback_locale]
```

---

## 🚀 Example Model

```php
use UltraLean\Core\Model;
use UltraLean\Core\I18n\Translatable;

class Post extends Model
{
    use Translatable;

    protected static string $table = 'posts';

    protected static string $translationTable = 'post_translations';
    protected static string $translationForeignKey = 'post_id';

    protected static array $translatable = ['title', 'content'];
}
```

---

# 🧠 Model (Active Record Layer)

## Purpose

Provides a **static, ultra-fast ORM-like interface** without heavy abstraction.

---

## ⚡ Features

* Zero object hydration (arrays only)
* Cached SQL for hot paths
* Built-in translation support
* No dependency on Query Builder

---

## 🔍 Basic Usage

### Find by ID

```php
$post = Post::find(1);
```

✅ Automatically translated

---

### Find Multiple

```php
$posts = Post::findMany([1, 2, 3]);
```

---

### Get All

```php
$posts = Post::all(100);
```

---

### Where

```php
$posts = Post::where('slug', 'hello-world');
```

---

### First Where

```php
$post = Post::firstWhere('slug', 'hello-world');
```

---

## ✍️ Insert

```php
$id = Post::insert([
    'slug' => 'new-post'
]);
```

---

## ✏️ Update

```php
Post::update(1, [
    'slug' => 'updated-post'
]);
```

---

## ❌ Delete

```php
Post::delete(1);
```

---

## 🔢 Count

```php
$count = Post::count();
```

---

## ✅ Exists

```php
$exists = Post::exists('slug', 'hello-world');
```

---

## 🔁 Transactions

```php
DB::transaction(function () {
    Post::insert(['slug' => 'a']);
    Post::insert(['slug' => 'b']);
});
```

---

## ⚡ Translation Behavior

All read methods automatically:

* Join translation table
* Apply locale
* Apply fallback

No extra code required.

---

# 🔗 Query Builder (QB)

## Purpose

Provides a **fluent interface** for building dynamic SQL queries.

---

## ⚡ Features

* Chainable API
* Automatic translation support (when using Model)
* Compiled SQL caching
* Zero runtime overhead after compile

---

## 🏗️ Basic Usage

### From Model

```php
$posts = Post::query()->get();
```

---

### Manual Table

```php
$rows = QB::table('posts')->get();
```

---

## 🔍 Select

```php
Post::query()
    ->select(['id', 'slug'])
    ->get();
```

---

## 🔎 Where

```php
Post::query()
    ->where('t.id', '=', 1)
    ->get();
```

---

## 🔗 Joins

```php
Post::query()
    ->join('users u', 'u.id = t.user_id')
    ->get();
```

---

## 📊 Ordering

```php
Post::query()
    ->orderBy('t.id', 'DESC')
    ->get();
```

---

## 📉 Limit / Offset

```php
Post::query()
    ->limit(10)
    ->offset(20)
    ->get();
```

---

## 🔢 Count

```php
$count = Post::query()->count();
```

---

## ✅ Exists

```php
$exists = Post::query()
    ->where('t.slug', '=', 'hello')
    ->exists();
```

---

## 🔄 Cursor (Streaming)

```php
Post::query()->cursor(function ($row) {
    // process row
});
```

---

## 🧪 Debugging

### View SQL

```php
Post::query()->toSql();
```

---

### Debug with Bindings

```php
Post::query()->debug();
```

---

### Debug Raw SQL

```php
Post::query()->debugRaw();
```

---

### Explain Query

```php
Post::query()->explain();
```

---

## 🌍 Translation Behavior in QB

Translation is applied automatically when:

* Using `Model::query()`
* Model uses `Translatable`
* `i18n.database.enabled = true`

---

## ❗ Important Notes

### QB without Model

```php
QB::table('posts')->get();
```

🚫 No translation applied

---

### QB with Model

```php
Post::query()->get();
```

✅ Translation applied automatically

---

# ⚙️ Configuration

## i18n Config

```php
return [
    'database' => [
        'enabled' => true,
    ],
    'fallback' => 'en',
];
```

---

# 🧠 Design Principles

* ⚡ Performance first (no unnecessary abstraction)
* 🔌 Decoupled architecture
* 🧱 SQL-first approach
* 🧩 Composable components
* 🚫 No magic, only predictable behavior

---

# 🚀 Summary

| Component    | Responsibility            |
| ------------ | ------------------------- |
| DB           | PDO execution             |
| Model        | Static ORM layer          |
| QB           | Query builder             |
| Translatable | SQL translation injection |

---

# ✅ Result

You get:

* Automatic translations everywhere
* No runtime overhead
* Clean, maintainable architecture
* Maximum performance

---

**UltraLean = Speed + Simplicity + Power**
