# 📄 `Middleware.md`

```md
# Middleware System (UltraLean)

Lightweight, zero-overhead middleware runner.

---

## 🚀 Register Middleware

```php
MiddlewareManager::instance()->register('auth', function () {
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        return false;
    }
});
📦 Class-based Middleware
namespace App\Middleware;

class Auth
{
    public function handle(): bool
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            return false;
        }
        return true;
    }
}
🔁 Run Middleware
MiddlewareManager::instance()->run(['auth']);
⚡ Stop Execution

Return false:

return false; // stops request lifecycle
🔄 Before / After Middleware
MiddlewareManager::instance()->register('log', function ($next) {
    error_log('Before');

    $res = $next();

    error_log('After');

    return $res;
});
🧠 Best Practice
Use middleware for:
Auth
Rate limiting
Logging
CSRF
⚡ Performance
Lazy discovery
Cached instances
No reflection
Minimal array lookup