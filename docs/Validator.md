


📄 Validator.md
# Validator (UltraLean)

Ultra-fast validation engine with optional DB support.

## ✅ Features

- Zero-overhead rule compilation
- Hybrid DB usage (auto or injected)
- Supports update scenarios
- Flash-friendly (external handling)

---

## 🚀 Usage

### Option A (FASTEST)
```php
$v = new Validator();
Option B (DI Container)
$v = app(Validator::class);
Option C (Manual PDO Injection)
$pdo = DB::conn();
$v = new Validator($pdo);
🧪 Validate
$data = [
    'email' => 'test@example.com',
    'password' => '123456'
];

$rules = [
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:6'
];

$v = new Validator();

if (!$v->validate($data, $rules)) {
    print_r($v->errors());
}
🔁 Update Mode
$v->validate($data, $rules, [], true);
❗ Error Handling
$v->errors();   // all errors
$v->fails();    // true if failed
$v->passes();   // true if success
📜 Supported Rules
Rule	Description
required	field required
string	must be string
alpha	letters only
alpha_num	alphanumeric
boolean	true/false
email	valid email
numeric	numeric
integer	integer
array	must be array
min:x	minimum length
max:x	maximum length
between:x,y	range
in:a,b	allowed values
not_in:a,b	disallowed
url	valid URL
ip	valid IP
date	valid date
regex:/.../	regex match
same:field	must match
unique:table,column	DB unique
exists:table,column	DB exists
🧠 Best Practice
if (!$v->validate($data, $rules)) {
    redirect_with_errors('/form', $v->errors(), $data);
}
⚡ Performance Notes
Rules compiled once
No reflection
No container overhead (Option A)
DB only used if needed



# UltraLean Validator Documentation

## Overview

The `Validator` class is a **high-performance, minimal-overhead validation engine** designed for modern PHP applications.

It provides:

* ⚡ Fast execution (precompiled rules)
* 🧠 Simple rule syntax
* 🌐 Multilingual-safe string handling (UTF-8 aware)
* 🔗 Database validation support (`unique`, `exists`)
* 🧩 Extensible architecture

---

## Basic Usage

```php
use UltraLean\Core\Validator;

$validator = new Validator();

$data = [
    'email' => 'test@example.com',
    'name'  => 'John'
];

$rules = [
    'email' => 'required|email',
    'name'  => 'required|min:3|max:50'
];

if ($validator->validate($data, $rules)) {
    echo "Valid!";
} else {
    print_r($validator->errors());
}
```

---

## Method Reference

### validate()

```php
validate(array $data, array $rules, array $messages = [], bool $isUpdate = false): bool
```

Validates input data against rules.

#### Parameters

* `$data` → Input data
* `$rules` → Validation rules
* `$messages` → Custom error messages
* `$isUpdate` → Skip missing fields (for PATCH/PUT)

#### Returns

* `true` → Passed
* `false` → Failed

---

### errors()

```php
errors(): array
```

Returns all validation errors.

---

### fails()

```php
fails(): bool
```

Returns `true` if validation failed.

---

### passes()

```php
passes(): bool
```

Returns `true` if validation passed.

---

---

## Rule Syntax

Rules can be defined as:

```php
'field' => 'required|email|min:5'
```

or

```php
'field' => ['required', 'email', 'min:5']
```

---

## Available Rules

### Basic Rules

| Rule     | Description             |
| -------- | ----------------------- |
| required | Field must not be empty |
| string   | Must be string          |
| numeric  | Must be numeric         |
| integer  | Must be integer         |
| boolean  | Must be boolean         |
| array    | Must be array           |

---

### String Rules (UTF-8 safe)

| Rule            | Description    |
| --------------- | -------------- |
| min:x           | Minimum length |
| max:x           | Maximum length |
| between:min,max | Range length   |

---

### Format Rules

| Rule          | Description  |
| ------------- | ------------ |
| email         | Valid email  |
| url           | Valid URL    |
| ip            | Valid IP     |
| regex:pattern | Custom regex |

---

### Value Rules

| Rule       | Description              |
| ---------- | ------------------------ |
| in:a,b,c   | Must be in list          |
| not_in:a,b | Must not be in list      |
| same:field | Must match another field |

---

### Database Rules

#### unique

```php
'email' => 'unique:users,email,id,1'
```

* table: `users`
* column: `email`
* primary key: `id`
* ignore ID: `1`

#### exists

```php
'user_id' => 'exists:users,id'
```

---

## Custom Messages

```php
$messages = [
    'email.required' => 'Email is required!',
    'email.email'    => 'Invalid email format'
];
```

---

## Multilingual Support

The validator uses:

* `strlen()` for ASCII (fast)
* `mb_strlen()` for UTF-8 (correct)

### Example

```php
$data = ['name' => 'سلام'];

$rules = ['name' => 'min:3'];

$validator->validate($data, $rules);
```

✔ Works correctly with Urdu, Arabic, emojis

---

## Example: Update Mode

```php
$validator->validate($data, $rules, [], true);
```

✔ Missing fields are ignored

---

## Example: Database Validation

```php
$rules = [
    'email' => 'required|email|unique:users,email,id,5'
];
```

---

## Performance Notes

* Rules are compiled once → faster execution
* No reflection or heavy OOP
* Minimal memory usage
* Optimized for high-load systems

---

## Best Practices

* Use array rules for better performance
* Avoid heavy regex when possible
* Use database rules sparingly (they are slower)

---

## Extending Validator

Add new rule:

```php
protected function validateCustom($field, $value, $param): bool
{
    return $value === 'something';
}
```

Register in rule map:

```php
'custom' => 'validateCustom'
```

---

## Summary

The Validator is:

* ⚡ Fast
* 🧠 Simple
* 🌍 Multilingual-safe
* 🔧 Extensible

Ideal for **high-performance PHP frameworks**
