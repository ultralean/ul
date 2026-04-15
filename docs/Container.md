# 📄 `Container.md` (DI System)

```md
# Dependency Injection Container (UltraLean)

Minimal, ultra-fast DI container.

---

## 🚀 Bind Services

```php
app()->bind('db', function () {
    return DB::conn();
});
🔒 Singleton
app()->singleton('logger', function () {
    return new Logger();
});
🎯 Resolve
$db = app('db');
🧠 Class Resolution
$validator = app(Validator::class);
⚡ Helper Function
function app($key = null) {
    return Container::instance()->get($key);
}
🧪 Example
app()->singleton(Validator::class, function () {
    return new Validator(DB::conn());
});

$v = app(Validator::class);
⚡ Performance Notes
Singleton caching
No reflection (manual binding)
Array lookup only
Closure executed once
🧠 When to Use
Scenario	Use
High performance	new Validator()
Clean architecture	app(Validator::class)