# Request Class

Namespace: `UltraLean\Core\Request`

A lightweight, high-performance wrapper around PHP superglobals (`$_GET`, `$_POST`, `$_FILES`, `$_SERVER`).

Designed for:
- ⚡ Zero overhead access
- ⚡ Lazy JSON parsing
- ⚡ Singleton reuse

---

## 🔹 Instance

```php
$request = Request::instance();
```

🔹 Methods
input(string $key, mixed $default = null): mixed

Get value from:

POST
GET
JSON body
$name = $request->input('name');
get(string $key, mixed $default = null): mixed
$page = $request->get('page', 1);
post(string $key, mixed $default = null): mixed
$email = $request->post('email');
json(): array

Lazy loads JSON body.

$data = $request->json();
all(): array

Merge of GET + POST + JSON

$data = $request->all();
🔹 Request Info
method(): string
if ($request->method() === 'POST') { ... }
header(string $key): ?string
$auth = $request->header('Authorization');
isJson(): bool
if ($request->isJson()) { ... }
wantsJson(): bool
if ($request->wantsJson()) { ... }
uri(): string
$uri = $request->uri();
path(): string
$path = $request->path();
🔹 File Handling
file(string $key): ?array
$file = $request->file('avatar');
files(): array
$files = $request->files();
moveFile(string $key, ?string $name = null): ?string
$path = $request->moveFile('avatar');

Uses:

config('uploads_path')
⚡ Performance Notes
JSON parsing is lazy
Headers are cached
Singleton avoids repeated instantiation
No unnecessary abstraction