<?php

namespace UltraLean\Core;

use RuntimeException;

class View
{
    protected string $basePath;

    protected ?string $layout = null;

    protected array $sections = [];
    protected array $sectionStack = [];

    protected array $componentStack = [];
    protected array $slots = [];

    protected bool $checkFiles = false;

    protected static array $pathCache = [];

    public function __construct(string $viewsPath, bool $checkFiles = false)
    {
        $this->basePath = rtrim($viewsPath, '/') . '/';
        $this->checkFiles = $checkFiles;
    }

    /* =========================
     * Layout & Sections
     * ========================= */

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $stack = &$this->sectionStack;

        if (!$stack) {
            throw new RuntimeException('No active section to end.');
        }

        $this->sections[array_pop($stack)] = ob_get_clean();
    }

    public function renderSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /* =========================
     * Components
     * ========================= */

    public function component(string $name, array $data = []): void
    {
        $file = $this->resolvePath('components/' . $name);

        if ($this->checkFiles && !is_file($file)) {
            throw new RuntimeException("Component not found: {$file}");
        }

        if ($data) extract($data, EXTR_SKIP);

        include $file;
    }

    public function startComponent(string $name, array $data = []): void
    {
        $this->componentStack[] = [$name, $data];
        ob_start();
    }

    public function endComponent(): void
    {
        $stack = &$this->componentStack;

        if (!$stack) {
            throw new RuntimeException('No component to end.');
        }

        [$name, $data] = array_pop($stack);

        ob_get_clean();

        $file = $this->resolvePath('components/' . $name);

        if ($this->checkFiles && !is_file($file)) {
            throw new RuntimeException("Component not found: {$file}");
        }

        if ($data) extract($data, EXTR_SKIP);
        if ($this->slots) extract($this->slots, EXTR_SKIP);

        $this->slots = [];

        include $file;
    }

    public function slot(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSlot(): void
    {
        $stack = &$this->sectionStack;

        if (!$stack) {
            throw new RuntimeException('No active slot to end.');
        }

        $this->slots[array_pop($stack)] = ob_get_clean();
    }

    /* =========================
     * Rendering (HOT PATH)
     * ========================= */

    public function render(string $view, array $data = []): string
    {
        // Inline resolve (faster than method call)
        if (!isset(self::$pathCache[$view])) {
            $v = $view[0] === '/' ? substr($view, 1) : $view;
            self::$pathCache[$view] = $this->basePath . $v . '.php';
        }

        $viewFile = self::$pathCache[$view];

        if ($this->checkFiles && !is_file($viewFile)) {
            throw new RuntimeException("View not found: {$viewFile}");
        }

        if ($data) extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;

        if ($this->layout !== null) {
            $content = ob_get_clean();

            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $content;
            }

            $layout = $this->layout;

            if (!isset(self::$pathCache[$layout])) {
                $l = $layout[0] === '/' ? substr($layout, 1) : $layout;
                self::$pathCache[$layout] = $this->basePath . $l . '.php';
            }

            ob_start();
            include self::$pathCache[$layout];

            $output = ob_get_clean();
            $this->reset();

            return $output;
        }

        $output = ob_get_clean();
        $this->reset();

        return $output;
    }

    /* =========================
     * Internal helpers
     * ========================= */

    protected function resolvePath(string $view): string
    {
        if (!isset(self::$pathCache[$view])) {
            $v = $view[0] === '/' ? substr($view, 1) : $view;
            self::$pathCache[$view] = $this->basePath . $v . '.php';
        }

        return self::$pathCache[$view];
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    protected function reset(): void
    {
        $this->layout = null;
        $this->sections = [];
        $this->sectionStack = [];
        $this->componentStack = [];

        if ($this->slots) {
            $this->slots = [];
        }
    }
}
