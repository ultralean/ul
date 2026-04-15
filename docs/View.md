# 📘 View Class Documentation

# UltraLean View Engine

## Overview

The `View` class is a **lightweight, high-performance PHP templating engine**.

It supports:
- Layouts
- Sections
- Components
- Slots

While maintaining near raw PHP execution speed.

## Key Features

- ⚡ Static path caching
- ⚡ Minimal abstraction
- ⚡ No template compilation
- ⚡ Opcode-friendly execution
- ⚡ Automatic state reset
- ⚡ Optional file checking

## Constructor

```php
public function __construct(string $viewsPath, bool $checkFiles = false)
Parameters
viewsPath → Base directory for views
checkFiles → Enable file existence checks (disable in production)
Layout System
extend()
public function extend(string $layout): void

Define layout file.

renderSection()
public function renderSection(string $name, string $default = ''): string

Render section content inside layout.

Sections
section()
public function section(string $name): void

Start a section buffer.

endSection()
public function endSection(): void

End section and store content.

Components
component()
public function component(string $name, array $data = []): void

Render a component directly.

Component + Slots
startComponent()
public function startComponent(string $name, array $data = []): void

Start component with slot support.

endComponent()
public function endComponent(): void

Render component with slots.

slot()
public function slot(string $name): void

Start slot buffer.

endSlot()
public function endSlot(): void

End slot buffer.

Rendering
render()
public function render(string $view, array $data = []): string

Renders a view:

Extracts data
Executes view file
Applies layout (if defined)
Returns final output
Escaping
e()
public static function e(mixed $value): string

HTML escape helper.

Internal Behavior
Path Resolution
Cached in static array
Avoids repeated filesystem operations
State Reset

After each render:

Layout cleared
Sections cleared
Slots cleared
Stacks cleared

Ensures safe reuse.

Example Usage
View File
<?php $this->extend('layouts/main'); ?>

<?php $this->section('content'); ?>
<h1>Hello <?= View::e($name) ?></h1>
<?php $this->endSection(); ?>
Layout File
<html>
<body>
    <?= $this->renderSection('content') ?>
</body>
</html>
Component Example
<?php $this->component('button', ['text' => 'Click Me']); ?>
Performance Notes
Uses include (OPCache optimized)
No template compilation
Minimal object overhead
Static path caching
Best Practices
Disable $checkFiles in production
Avoid deep nesting of components
Use sections only when needed
Keep views simple for best performance
Performance Summary
Feature	Cost
Raw include	🟢 minimal
Sections	🟡 moderate
Components	🟡 moderate
Slots	🟡 moderate
Design Philosophy

"Stay as close to raw PHP as possible while enabling structured templating."