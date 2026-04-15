# 📄 `Cookie.md`

```md
# Cookie Class

Namespace: `UltraLean\Core\Cookie`

A static utility class for managing HTTP cookies using modern PHP `setcookie()` API.

Supports:
- Secure cookies
- HttpOnly protection
- SameSite control
- HTTPS-aware defaults

---

## 🔹 Design Principles

- Static utility (no instantiation)
- Direct PHP `setcookie()` usage
- Minimal abstraction overhead
- Secure defaults enabled by default

---

## 🔹 Methods

---

### set(string $name, string $value, array $options = []): void

Set a cookie with optional configuration.

```php id="c1a1"
Cookie::set('theme', 'dark');
🔹 Options
Option	Type	Default	Description
expires	int	+1 hour	Expiration timestamp
path	string	/	Cookie path
domain	string	''	Cookie domain
secure	bool	auto	HTTPS only if available
httponly	bool	true	Prevent JS access
samesite	string	Lax	Lax / Strict / None
Example with options
Cookie::set('auth', 'token123', [
    'expires' => time() + 86400,
    'secure' => true,
    'samesite' => 'Strict'
]);
🔹 get(string $name, mixed $default = null): mixed

Retrieve a cookie value.

$theme = Cookie::get('theme', 'light');
🔹 has(string $name): bool

Check if cookie exists.

if (Cookie::has('auth')) {
    // user has cookie
}
🔹 delete(string $name, array $options = []): void

Delete a cookie by expiring it.

Cookie::delete('auth');

Internally sets expiration to past time.

🔹 Internal Behavior
Secure Default Handling

If HTTPS is detected:

$_SERVER['HTTPS']

Then:

secure = true
⚡ Performance Notes
No object creation
Direct setcookie() usage
Minimal array merging overhead
No dependency on session system
Extremely lightweight
🔐 Security Notes
Always use httponly = true for auth cookies
Prefer samesite = Strict for sensitive cookies
Enable secure in production (HTTPS)