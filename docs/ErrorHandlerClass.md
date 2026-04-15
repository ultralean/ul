# ErrorHandler Class Documentation

Namespace: `UltraLean\Core`

A centralized error and exception handler that provides:

- Unified handling of exceptions, errors, and fatal shutdowns
- Environment-aware error rendering (development vs production)
- JSON / HTML / CLI output support
- Automatic request tracking via request ID
- Integrated logging support

---

## 📦 Dependencies

- `UltraLean\Core\Logger`
- PHP `Throwable`
- PHP `ErrorException`

---

## 🚀 Features

- Converts PHP errors into exceptions
- Handles fatal errors via shutdown handler
- Outputs:
  - JSON (API / AJAX / forced)
  - HTML (browser)
  - CLI-friendly output
- Automatically logs errors with context
- Includes request metadata (URL, method, IP)
- Adds unique `request_id` for tracing

---

## 🏗️ Initialization

```php
use UltraLean\Core\ErrorHandler;
use UltraLean\Core\Logger;

$logger = new Logger();
$errorHandler = new ErrorHandler($logger);
$errorHandler->register();
```

## ⚙️ Configuration

Controlled via config.php:

```php
'app' => [
    'env' => 'development', // development | production
    'force_json' => false,
],
```

```php
'logging' => [
    'enabled' => true,
],
```

## 🧠 Environment Behavior

| Environment | Behavior                            |
| ----------- | ----------------------------------- |
| production  | Safe error messages, no stack trace |
| development | Full debug output                   |

## 🧩 Handler Registration

```php
$errorHandler->register();
```

Registers:

```php
set_exception_handler
set_error_handler
register_shutdown_function
```

## 🔥 Exception Handling

```php
Method
handleException(Throwable $e): void
```

Responsibilities

```php
Clears output buffer
Determines HTTP status code
Sends headers
Logs error (if enabled)
Renders response (CLI / JSON / HTML)
```

## ⚠️ Error Handling

```php
Method
handleError(int $severity, string $message, string $file, int $line): bool
```

Behavior

```php
Converts non-fatal PHP errors into ErrorException
Respects error_reporting()
Skips fatal errors (handled in shutdown)
```

## 💥 Shutdown Handling

```php
Method:
handleShutdown(): void  
```

Handles:

```php
Fatal errors:
E_ERROR
E_PARSE
E_CORE_ERROR
E_COMPILE_ERROR
```

Converts them into exceptions and passes to handleException()

## 📡 Response Type Detection

```php
Method:
expectsJson(): bool

Returns true if:
- force_json = true
- Accept: application/json
- URL starts with /api/
- AJAX request (XMLHttpRequest)

## 🌐 JSON Response Format

Example (Production)

```php
{
  "error": true,
  "message": "Server error",
  "request_id": "abc123xyz"
}

Example (Development)

```php
{
  "error": true,
  "message": "Undefined variable",
  "request_id": "abc123xyz",
  "type": "ErrorException",
  "file": "/app/file.php",
  "line": 42
}
```

Validation Errors Support

If exception has:

```php
getErrors()
```

Then response includes:

```php
"errors": { ... }
```

## 🖥️ CLI Output

```php
[ERROR] ExceptionClass
Message
/path/file.php:123

Stack trace...
```

## 🌍 HTML Output

Production

```php
<h1>Something went wrong</h1>
<p>An unexpected error occurred. Please try again later.</p>
```

Development

```php
<pre>Full exception stack trace...</pre>
```

## 🧾 Logging Integration

Errors are logged using Logger if enabled:

```php
$this->logger->error($e->getMessage(), $context);
```

Context Includes

```php
[
    'exception' => $e,
    'request_id' => 'unique-id',
    'url' => '/current/url',
    'method' => 'GET',
    'ip' => '127.0.0.1',
]
```

## 🆔 Request ID

Each request gets a unique ID:

bin2hex(random_bytes(8))

Used for:

Debugging

Log correlation

API responses

## 🔢 Status Code Resolution

```php
Method:
resolveStatusCode(Throwable $e): int

Behavior:
- Uses $e->getStatusCode() if available
- Defaults to 500
```

## 🧼 Output Buffer Handling


Method

```php
cleanOutputBuffer(): void
```

Purpose:

- Prevents partial/broken responses
- Ensures clean error output

## 🧪 Example Usage

```php
$logger = new Logger();

$errorHandler = new ErrorHandler($logger);
$errorHandler->register();

// Any error/exception after this point is handled globally
```

## 🛑 When Logging May Fail

If logging throws an exception:

In production → silently ignored

In development → error message is displayed

## 🧱 Internal Flow

Error/Exception occurs

Converted to Throwable (if needed)

Output buffer cleared

Status code resolved

Error logged

Response format determined:

CLI → text output

JSON → structured response

HTML → friendly/dev page

## 🧼 Best Practices

Always register early (bootstrap phase)

Use with a robust logger (like provided Logger)

Avoid exposing sensitive data in exceptions

Use custom exceptions with:

```php
getStatusCode()

getErrors() (for validation)
```