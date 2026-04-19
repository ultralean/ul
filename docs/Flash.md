# 📄 `Flash.md`

```md
# Flash System (UltraLean)

Session or cookie-based flash messaging system.

---

## 🚀 Set Flash

```php
flash_set('success', 'Saved successfully');
📥 Get Flash
echo flash_get('success');
📦 All Flash
flash_all();
🔁 Redirect with Errors
redirect_with_errors('/form', $errors, $oldInput);
🔁 Redirect with Message
redirect_with_message('/home', 'success', 'Done');
🧠 Old Input
old('email');
🧾 Form Helpers
<input type="text" <?= form_text('name') ?>>

<textarea><?= form_textarea('bio') ?></textarea>

<input type="checkbox" <?= form_checked('agree', 1) ?>>
❗ Errors
error('email');
has_error('email');
error_text('email');
⚡ Lifecycle
flash_init();
⚙️ Modes
Mode	Description
Session	default
Cookie	stateless
🧠 Best Practice
Use with Validator
Never mix inside Validator


# ⚡ UltraLean Flash & Form Binding System

A **high-performance, zero-overhead flash messaging and form binding system** for UltraLean.

Supports:

* ✅ Session-based flash (default, recommended)
* ⚡ Stateless cookie mode (optional)
* 🔁 Old input retention
* 🧾 Auto form binding (inputs, textarea, select, checkbox, etc.)
* ❗ Validation errors handling
* 🔐 Secure cookie signing (stateless mode)

---

# 📁 Configuration

## File

```
/apps/default/config.php
```

## Example

```php
'flash' => [
    'use_cookies' => false,
    'cookie_key' => '_ul_flash',
    'cookie_secret' => 'change_this_secret',
],
```

---

# 🚀 Bootstrap Integration

## Add to Bootstrap

```php
define('FLASH_USE_COOKIES', config('flash.use_cookies', false));
define('FLASH_COOKIE_KEY', config('flash.cookie_key', '_ul_flash'));
define('FLASH_COOKIE_SECRET', config('flash.cookie_secret', 'change_this_secret'));

require SYSTEM_PATH . '/helpers/flash.php';

if (!FLASH_USE_COOKIES && session_status() === PHP_SESSION_NONE) {
    session_start();
}

flash_init();
```

---

# 🔁 Flash Data

Flash data is available for **one request only**.

---

## Set Flash

```php
flash_set('success', 'Saved successfully');
```

---

## Get Flash

```php
echo flash_get('success');
```

---

## Check Flash

```php
if (flash_has('success')) {
    echo flash_get('success');
}
```

---

## Get All Flash

```php
$data = flash_all();
```

---

## Clear Flash

```php
flash_clear();
```

---

# 🔁 Redirect Helpers

---

## Redirect with Errors

```php
redirect_with_errors('/form', [
    'email' => ['Email is required']
], $_POST);
```

---

## Redirect with Message

```php
redirect_with_message('/dashboard', 'success', 'Profile updated');
```

---

# 🧠 Old Input Handling

---

## Get Old Value

```php
<input name="email" value="<?= old('email') ?>">
```

---

## Raw Value (No Escape)

```php
$value = old_raw('email');
```

---

# 🧾 Auto Form Binding

Eliminates manual value handling.

---

## Text Input

```php
<input type="text" name="email" <?= form_value('email') ?>>
```

---

## Email Input

```php
<input type="email" name="email" <?= form_email('email') ?>>
```

---

## Password (Never Repopulated)

```php
<input type="password" name="password" <?= form_password() ?>>
```

---

## Textarea

```php
<textarea name="message"><?= form_textarea('message') ?></textarea>
```

---

## Checkbox

```php
<input type="checkbox" name="roles[]" value="admin" <?= form_checked('roles', 'admin') ?>>
```

---

## Radio

```php
<input type="radio" name="gender" value="male" <?= form_checked('gender', 'male') ?>>
```

---

## Select

```php
<option value="pk" <?= form_selected('country', 'pk') ?>>Pakistan</option>
```

---

## File Input

```php
<input type="file" name="avatar" <?= form_file() ?>>
```

---

# ❗ Validation Errors

---

## Get All Errors

```php
$errors = errors();
```

---

## Get Single Error

```php
echo error('email');
```

---

## Check Error

```php
if (has_error('email')) {
    // handle error
}
```

---

## Add Error Class

```php
<input name="email" class="<?= error_class('email') ?>">
```

---

## Show Error Message

```php
<?= error_text('email') ?>
```

---

## Custom Wrapper

```php
<?= error_text('email', '<span class="text-danger">%s</span>') ?>
```

---

# 🍪 Stateless Mode (Cookie-Based)

Enable in config:

```php
'use_cookies' => true
```

---

## How It Works

* Stores flash in cookies
* No session required
* Data is signed using HMAC

---

## Use Cases

* APIs
* Stateless apps
* Microservices

---

## Limitations

* ~4KB size limit
* Sent on every request
* Slightly slower than sessions

---

# ⚖️ Session vs Cookie Mode

| Feature    | Session   | Cookie            |
| ---------- | --------- | ----------------- |
| Speed      | 🟢 Fastest | 🟡 Slight overhead |
| Storage    | Server    | Client            |
| Security   | High      | Needs signing     |
| Size limit | None      | ~4KB              |

---

## ✅ Recommendation

Use **session mode**:

```php
'use_cookies' => false
```

---

# 🔁 Lifecycle

### Request 1

```php
flash_set('success', 'Saved');
```

---

### Request 2

```php
flash_init();
echo flash_get('success'); // "Saved"
```

---

### Request 3

```php
flash_get('success'); // null
```

---

# ⚡ Performance

| Operation        | Complexity |
| ---------------- | ---------- |
| Flash read/write | O(1)       |
| Session access   | O(1)       |
| Cookie decode    | O(n) small |
| Form binding     | O(1)       |

---

# 🧠 Best Practices

* ✅ Call `flash_init()` once in bootstrap
* ✅ Use `form_*` helpers for all inputs
* ✅ Always escape output (handled automatically)
* ✅ Keep cookie mode only for stateless systems
* ❌ Do not store large data in flash

---

# 🏁 Summary

UltraLean Flash System provides:

* ⚡ Near raw PHP performance
* 🧠 Clean API (no ambiguity)
* 🔁 Automatic lifecycle
* 🧩 Session + Stateless support
* 🧾 Full form binding system

---

**You now have a production-grade flash + form system.**
