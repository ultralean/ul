# Session Class

Namespace: `UltraLean\Core\Session`

A lightweight, static session manager built on top of native PHP sessions.

Designed for:
- ⚡ Lazy session initialization
- ⚡ Zero overhead access to `$_SESSION`
- ⚡ Secure session lifecycle management
- ⚡ Minimal abstraction layer over PHP core

---

## 🔹 Design Principles

- Sessions start only when needed
- No persistent object state
- Direct access to PHP session storage
- Safe for high-performance applications

---

## 🔹 Methods

---

### set(string $key, mixed $value): void

Store a value in session.

```php id="s1a1"
Session::set('user_id', 10);
get(string $key, mixed $default = null): mixed

Retrieve a session value.

$userId = Session::get('user_id');
has(string $key): bool

Check if a session key exists.

if (Session::has('user_id')) {
    // user is logged in
}
delete(string $key): void

Remove a specific session key.

Session::delete('user_id');
all(): array

Get all session data.

$data = Session::all();
clear(): void

Clear all session data (session remains active).

Session::clear();
destroy(): void

Completely destroy session and remove session cookie.

Session::destroy();

Includes:

session data cleanup
session termination
cookie removal
regenerate(): void

Regenerate session ID (security feature).

Session::regenerate();
🔹 Internal Behavior
Lazy Start

Session starts automatically when needed:

session_start()

Only executed if:

session_status() !== PHP_SESSION_ACTIVE
⚡ Performance Notes
No object instantiation required
Session starts only when accessed
Direct superglobal usage
Minimal function overhead
Safe for high-frequency requests
🔐 Security Notes
Use regenerate() after login
Use destroy() on logout
Always validate session data before trust