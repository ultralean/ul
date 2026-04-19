# 🔐 Security & Rate Limiting (UltraLean Framework)

This document explains the built-in **CSRF, CORS, CSP, and Rate Limiting** system in UltraLean, including global and per-route throttling.

---

# 📦 Overview

UltraLean provides:

- ✅ CSRF Protection (configurable, route-based skip)
- 🌐 CORS Handling (early exit, zero overhead)
- 🛡️ CSP Headers (static, config-driven)
- 🚦 Rate Limiting
  - Global (applied to all requests)
  - Per-route (`throttle:x,y`)
  - APCu (fast) + File fallback
  - Automatic cleanup

---

# 🔐 CSRF Protection

## ✅ Features

- Session-based token
- Automatic validation for state-changing requests
- Config-based route exclusion (e.g. APIs)
- Supports AJAX via headers

---

## ⚙️ Config

```php
'security' => [
    'csrf' => [
        'enabled' => true,
        'except' => [
            '/api', // skip CSRF for API routes
        ],
        'header' => 'X-CSRF-TOKEN',
    ],
],
🧾 Usage in Forms
<form method="POST">
    <?= csrf_field() ?>
</form>
🔁 AJAX Example
fetch('/submit', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': 'your_token_here'
    }
});
⚠️ Notes
CSRF applies only to:
POST
PUT
PATCH
DELETE
Routes in except are skipped
🌐 CORS (Cross-Origin Resource Sharing)
✅ Features
Global header-based configuration
Handles preflight requests automatically
Zero overhead for normal requests
⚙️ Config
'cors' => [
    'enabled' => true,
    'allow_origin'  => '*',
    'allow_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
    'allow_headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN',
],
⚡ Behavior
Adds headers to every response
Automatically exits on OPTIONS request with 204
🛡️ CSP (Content Security Policy)
✅ Features
Static header (no runtime cost)
Fully config-driven
⚙️ Config
'csp' => [
    'enabled' => true,
    'policy' => "default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline';",
],
📌 Example Header
Content-Security-Policy: default-src 'self'; ...
🚦 Rate Limiting
📌 Overview

UltraLean provides:

Global rate limiting (applies to all requests)
Per-route throttling using middleware (throttle:x,y)
APCu (fastest) with automatic file fallback
Automatic storage cleanup
⚙️ Global Rate Limiting
Config
'rate_limit' => [
    'enabled' => true,
    'max' => 100,
    'window' => 60, // seconds
],
Behavior
100 requests per 60 seconds → allowed
101st request → blocked (429)
🎯 Per-Route Rate Limiting
Usage
Router::post('/login', 'Auth@login', ['throttle:5,60']);
Meaning
throttle:5,60
→ 5 requests per 60 seconds
Example Routes
Router::post('/login', 'Auth@login', ['throttle:5,60']);
Router::post('/contact', 'Contact@send', ['throttle:3,60']);
Router::get('/api/data', 'Api@get', ['throttle:100,60']);
Behavior
5 requests → allowed
6th request → blocked (429 Too Many Requests)
🧠 How Rate Limiting Works

Each request generates a unique key:

IP | PATH | METHOD

Example:

192.168.1.1|/login|POST

This ensures:

One route does not affect others
Query strings do not create duplicates
Method-specific limiting
⚡ Storage Drivers
1. APCu (Preferred)
In-memory (fastest)
O(1) access
Automatically used if available
2. File Storage (Fallback)

Location:

storage/rate/

Each key creates one file:

md5(IP|URI|METHOD)
Example File Content
[3, 1713500000]

Meaning:

3 requests made
window started at timestamp
🧹 Automatic Cleanup

File-based limiter includes lazy cleanup:

Runs ~1% of requests
Deletes expired files
Uses filemtime() (fast)
Cleanup Logic
if (mt_rand(1, 100) === 1) {
    rate_limit_cleanup(...);
}
Benefits
No cron jobs required
No disk bloat
Minimal overhead
🔐 Best Practices
Protect Sensitive Routes
Router::post('/login', 'Auth@login', ['throttle:5,60']);
Router::post('/password-reset', 'Auth@reset', ['throttle:2,300']);
Combine Global + Route Limits
Type	Purpose
Global	Basic protection
Route	Sensitive endpoints
Use Smart Keys (Already Implemented)

Includes:

IP
Path
Method
Optional (Advanced)

Use user-based key:

$userId = session('user_id') ?? 'guest';

$key = $userId . '|' . $ip . '|' . $uri;

✅ Summary

UltraLean provides:

🔐 Secure CSRF system (configurable)
🌐 Fast CORS handling
🛡️ CSP with zero overhead
🚦 Powerful rate limiting:
Global + per-route
APCu + file fallback
Auto cleanup
Minimal resource usage
🚀 Example (Complete)
Router::post('/login', 'Auth@login', ['throttle:5,60']);
5 requests → OK
6th request → 429 Too Many Requests
💡 Final Notes
Designed for performance-first applications
No heavy middleware chains
No database dependency
Works on shared hosting + VPS

UltraLean = Fast, Secure, Minimal