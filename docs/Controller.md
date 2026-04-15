# UltraLean Core Controller

## Overview

The `Controller` class is a **minimal, high-performance base controller** designed to stay extremely close to raw PHP execution speed.

It provides:
- Thin request abstraction
- Delegated response handling
- Safe view rendering
- Zero unnecessary layers

---

## Key Features

- ⚡ Minimal constructor (singleton Request injection)
- ⚡ Thin Request abstraction (near-zero overhead)
- ⚡ Delegates all output to Response class
- ⚡ Lazy-loaded View engine (state-safe)
- ⚡ Hybrid rendering (raw + full engine)

---

## Architecture Principle

Controller does NOT handle output directly.

All responses are delegated to:


UltraLean\Core\Response


---

## Methods

### callAction()

```php
public function callAction(string $method, array $params = [])

Executes a controller method.

Request Helpers
input()
$this->input('name');
get()
$this->get('page');
post()
$this->post('email');
json()
$this->json();

Returns parsed JSON body.

Response Helpers
respond()
$this->respond(['success' => true]);

Auto-detects response type:

Array → JSON
String → HTML

Respects:

config('app.force_json')
redirect()
$this->redirect('/login');
abort()
$this->abort(404, 'Not Found');
back()
$this->back();

Redirect to previous page.

url()
$this->url('users/profile');

Uses:

config('base_url')
View Rendering
view()
$this->view('home.index', ['name' => 'Imran']);

Uses full View engine:

layouts
sections
components
rawView()
$this->rawView('home.index');

Fastest rendering (direct include)

Internal
getView()
Lazy-loaded View instance
Per-controller instance (NOT shared globally)
Prevents layout/section conflicts
Performance Notes
Near raw PHP performance
No container, no reflection
Minimal abstraction
Request is singleton (no repeated instantiation)
Best Practices
Use respond() for APIs
Use view() for templating
Use rawView() for maximum performance
Avoid unnecessary data passing