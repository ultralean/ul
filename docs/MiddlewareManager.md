# 📄 `MiddlewareManager.md`

```md
# MiddlewareManager

Handles registration + discovery.

---

## 🚀 Run Middleware

```php
MiddlewareManager::instance()->run(['auth']);
🔍 Auto Discovery
Loads from:
apps/App/Middleware/
📦 Naming Convention
Auth.php → 'auth'
⚡ Performance
Runs once
Cached
No repeated scanning

---

# 📄 `Architecture.md`

```md
# UltraLean Architecture

## 🔥 Philosophy

- Pure PHP speed
- No heavy abstractions
- Optional DI
- Zero magic

---

## ⚡ Flow

Request → Middleware → Controller → Validator → Model → Response

---

## 🧠 Key Decisions

### Validator
- No flash inside
- DB optional

### Middleware
- Lazy loaded
- Callable or class

### DI
- Optional
- No reflection

### DB
- Static + cached PDO

---

## 🚀 Performance Strategy

- Static caching
- No reflection
- No annotations
- Minimal object creation

---

## 🧪 Example Flow

```php
MiddlewareManager::instance()->run(['auth']);

$v = new Validator();

if (!$v->validate($_POST, $rules)) {
    redirect_with_errors('/form', $v->errors(), $_POST);
}

User::insert($_POST);

redirect('/success');
🧠 Summary
Component	Strategy
Validator	Hybrid
Middleware	Lazy
DI	Optional
DB	Static PDO
Flash	External