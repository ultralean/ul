# UltraLean i18n System (Translation Layer)

UltraLean i18n provides **two independent translation systems**:

1. 🌍 Static translations (file-based UI translations)
2. 🗄️ Dynamic translations (database content translations)

Both systems are fully independent, highly optimized, and designed for **zero overhead when disabled**.

---

# 1. Architecture Overview


┌──────────────────────┐
│ Locale Resolver │ → determines current language
└──────────────────────┘

┌──────────────────────┐
│ Translator (Static) │ → file-based UI translations
└──────────────────────┘

┌──────────────────────┐
│ Model Translations │ → DB-based content translations
└──────────────────────┘


---

# 2. Locale System

## Class: `UltraLean\Core\I18n\Locale`

Responsible for resolving and storing the **current application locale**.

---

## Features

- Fast in-memory resolution
- Session-based persistence
- No database dependency
- O(1) lookup performance

---

## Set Locale

```php id="loc_set_001"
Locale::set('ur');
Get Current Locale
$locale = Locale::get();
Clear Locale
Locale::clear();
Resolution Order
Manually set locale (runtime override)
Session locale ($_SESSION['app_locale'])
Default locale (config fallback)
Example Flow
User session → ur
App → Locale::get() → ur
3. Configuration
'i18n' => [
    'enabled' => true,
    'default' => 'en',
    'fallback' => 'en',

    'supported' => ['en', 'ur', 'ar'],

    'path' => APP_PATH . '/lang',

    'resolver' => [
        'session' => true,
    ],

    'database' => [
        'enabled' => true
    ],
],
Meaning of Options
Key	Description
enabled	Enable/disable entire i18n system
default	Default language if none found
fallback	Fallback language when translation missing
supported	Allowed languages
path	Static translation file path
resolver.session	Store locale in session
database.enabled	Enable DB translation system
4. Static Translator (File-Based)
Class: UltraLean\Core\I18n\Translator

Handles UI translations and static text.

Purpose

Used for:

UI labels
buttons
menus
system messages
forms
Example Language File
/lang/en/messages.php
return [
    'welcome' => 'Welcome',
    'logout' => 'Logout',

    'auth' => [
        'failed' => 'Invalid credentials'
    ]
];
Usage
$translator->get('messages.welcome');
Nested Keys
$translator->get('messages.auth.failed');
With Replacements
$translator->get('messages.hello', [
    'name' => 'Ali'
]);

Example string:

'hello' => 'Hello :name'
Fallback Behavior

Order:

Current locale
Fallback locale
Key itself
Performance Design
Lazy file loading
Cached per request
No database usage
No reflection
O(1) array lookup
5. Translation Loading Mechanism
Group-based loading

A translation key is split into:

messages.welcome
↓
group = messages
key   = welcome
File resolution
/lang/{locale}/{group}.php

Example:

/lang/en/messages.php
/lang/ur/messages.php
Loaded cache (per request)
$this->loaded[$locale][$group]
$this->translations[$locale][$group]

Prevents repeated file inclusion.

6. Dynamic Translation System (Database Layer)
Purpose

Used for:

blog posts
products
categories
CMS content
Implemented via Model Layer

Dynamic translations are NOT handled by Translator class.

Instead they are handled via:

Model + Translatable trait + QueryBuilder injection
Example Schema
CREATE TABLE post_translations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    post_id BIGINT NOT NULL,
    locale CHAR(5) NOT NULL,

    title VARCHAR(255),
    content TEXT,

    UNIQUE KEY uniq_post_locale (post_id, locale),
    INDEX idx_locale (locale),
    INDEX idx_post_locale (post_id, locale)
);
How it works

When you run:

Post::query()->get();

System automatically:

joins translation table
injects locale filter
applies COALESCE fallback
Example SQL generated
SELECT
  t.*,
  COALESCE(tr.title, tf.title) AS title
FROM posts t
LEFT JOIN post_translations tr
  ON tr.post_id = t.id
 AND tr.locale = ?

LEFT JOIN post_translations tf
  ON tf.post_id = t.id
 AND tf.locale = ?
Result
[
  "id" => 1,
  "title" => "Hello World",
  "content" => "Translated content"
]
7. Translation Separation Principle
IMPORTANT RULE
System	Purpose
Translator	Static UI text
Model Translation	Dynamic DB content

They NEVER overlap.

Why separation matters
Prevents unnecessary DB queries for UI text
Keeps SQL layer optimized
Keeps file-based system ultra-fast
Avoids coupling between UI and content
8. Performance Design
Static Translation
File-based
Cached in memory
No DB calls
O(1) array access
Dynamic Translation
SQL JOIN based
Indexed tables
No PHP post-processing
COALESCE handled in DB
Locale Resolution
Session-based
No DB dependency
Single static memory value per request
9. Optimization Rules
❗ Rule 1

Never use DB for static translations.

❗ Rule 2

Never use file system for dynamic content translations.

❗ Rule 3

Locale must be resolved once per request.

❗ Rule 4

Fallback logic must be handled in SQL (for DB translations).

10. When to Use What
Use Translator (static) when:

✔ UI text
✔ buttons
✔ labels
✔ menus
✔ system messages

Use DB translation when:

✔ posts
✔ products
✔ categories
✔ CMS content

11. Summary

UltraLean i18n provides:

⚡ Ultra-fast locale resolution
🌍 File-based static translation system
🗄️ Database-based dynamic translation system
⚡ Fully separated concerns
⚡ Zero overhead when disabled
⚡ SQL-level performance optimization
12. Final Philosophy

Static text belongs in files.
Dynamic content belongs in database.
Locale is just a lightweight pointer.