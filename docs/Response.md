# 📄 `Response.md`

```md
# Response Class

Namespace: `UltraLean\Core\Response`

Handles all HTTP responses.

Features:
- ⚡ Centralized output
- ⚡ Config-driven (`force_json`)
- ⚡ Automatic response detection
- ⚡ Zero overhead logging integration

---

## 🔹 Methods

### json(mixed $data, int $status = 200): void

```php
Response::json(['success' => true]);
html(string $html, int $status = 200): void
Response::html('<h1>Hello</h1>');

⚠️ If force_json = true:

{ "html": "<h1>Hello</h1>" }
text(string $text, int $status = 200): void
Response::text('Hello');
redirect(string $url, int $status = 302): void
Response::redirect('/login');
download(string $file, ?string $name = null): void
Response::download('/path/report.pdf');
auto(mixed $data, int $status = 200): void

Smart response handler:

Response::auto(['ok' => true]); // JSON
Response::auto('<h1>Hello</h1>'); // HTML
error(int $code, string $message = ''): void
Response::error(404, 'Not Found');
Logs automatically (if enabled)
Shows detailed message in development
🔹 Headers

Automatically sends:

X-Request-ID: <unique_id>
⚡ Config Integration

Uses:

config('app.force_json')
⚡ Performance Notes
No buffering
No middleware overhead
Logging only runs if enabled
Minimal header checks