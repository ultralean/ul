# Logger Class Documentation

Namespace: `UltraLean\Core`

A lightweight logging wrapper built on top of **Monolog**, providing:
- Environment-aware logging (production vs development)
- Automatic log rotation
- Caller context injection
- Structured exception logging

---

## 📦 Dependencies

- `monolog/monolog`
- PHP `Throwable`

---

## 🚀 Features

- Supports all standard log levels (`debug` → `emergency`)
- Auto-creates log directory
- Rotating or single log file support
- Environment-based logging control
- Automatically attaches caller (class@method)
- Exception logging with full stack trace

---

## 🏗️ Initialization

```php
use UltraLean\Core\Logger;

$logger = new Logger();
```

## ⚙️ Configuration

Logging behavior is controlled via the config.php file:

```php
'logging' => [
    'enabled' => true,
    'level' => 'error',

    'enabled_in_development' => true,
    'level_in_development' => 'debug',

    'rotate' => true,
    'max_files' => 120,
],
```

### Config Options

| Key                    | Description                   |
| ---------------------- | ----------------------------- |
| enabled                | Master switch for logging     |
| level                  | Log level in production       |
| enabled_in_development | Enable logging in dev         |
| level_in_development   | Log level in development      |
| rotate                 | Enable daily log rotation     |
| max_files              | Number of days to retain logs |

## 📁 Log File Location

Logs are stored at:

STORAGE_PATH/logs/app-{APP}.log

Example:

storage/logs/app-myapp.log

## 🧠 Environment Behavior

| Environment | Logging Behavior                           |
| ----------- | ------------------------------------------ |
| production  | Always logs                                |
| development | Logs only if enabled_in_development = true |

## 🪵 Available Log Methods

Each method accepts:

(string $message, array $context = [])

### Methods

```php
$logger->debug('Debug message');
$logger->info('Info message');
$logger->notice('Notice message');
$logger->warning('Warning message');
$logger->error('Error message');
$logger->critical('Critical message');
$logger->alert('Alert message');
$logger->emergency('Emergency message');
```
### Generic Log Method

```php
$logger->log('info', 'Custom log level message', [
    'user_id' => 123
]);
```

## 🔍 Context Data

You can pass additional structured data:

```php
$logger->info('User logged in', [
    'user_id' => 42,
    'ip' => $_SERVER['REMOTE_ADDR']
]);
```

## 📌 Automatic Caller Injection

If no caller is provided in context, the logger automatically adds:

ClassName@methodName

Example output:

[INFO] User logged in {"caller":"AuthController@login"}

## ❗ Exception Logging

```php
try {
    // risky code
} catch (Throwable $e) {
    $logger->exception($e);
}
```

### Output Includes

- Exception class
- Message
- File & line
- Full stack trace

## 🔄 Log Rotation

If enabled:

'rotate' => true

Logs are rotated daily using:

RotatingFileHandler

Retains logs based on max_files

Example:

app-myapp-2026-04-05.log
    
## 🎯 Log Level Resolution

| Environment | Log Level                              |
| ----------- | -------------------------------------- |
| production  | config('logging.level')                |
| development | config('logging.level_in_development') |

## 🛑 When Logs Are Skipped

Logging will NOT occur if:

- logging.enabled = false
- Environment is development AND enabled_in_development = false

## 🧱 Internal Flow

1. Load config
2. Create log handler (rotating or stream)
3. Determine log level based on environment
4. Inject caller info
5. Write to log

## 🧪 Example Usage

```php
$logger = new Logger();

$logger->info('Application started');

$logger->error('Something went wrong', [
    'order_id' => 99
]);
```

## 🧩 Notes

- Uses LineFormatter with inline line breaks enabled
- Log levels are case-insensitive
- Defaults to DEBUG if invalid level is provided

## 📌 Requirements

- PHP 8+
- Writable storage/logs directory

## 🧼 Best Practices

- Use info() for business events
- Use warning() for recoverable issues
- Use error() for failures
- Use exception() for caught exceptions
- Avoid logging sensitive data

## 🛑 When Logs Are Skipped

Logging will NOT occur if:

- logging.enabled = false
- Environment is development AND enabled_in_development = false

## 🧱 Internal Flow

1. Load config
2. Create log handler (rotating or stream)
3. Determine log level based on environment
4. Inject caller info
5. Write to log

## 🧪 Example Usage

```php
$logger = new Logger();

$logger->info('Application started');

$logger->error('Something went wrong', [
    'order_id' => 99
]);
```

## 🧩 Notes

- Uses LineFormatter with inline line breaks enabled
- Log levels are case-insensitive
- Defaults to DEBUG if invalid level is provided

## 📌 Requirements

- PHP 8+
- Writable storage/logs directory

## 🧼 Best Practices

- Use info() for business events
- Use warning() for recoverable issues
- Use error() for failures
- Use exception() for caught exceptions
- Avoid logging sensitive data