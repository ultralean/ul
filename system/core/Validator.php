<?php

namespace UltraLean\Core;

use InvalidArgumentException;
use PDO;
use System\Core\DB;

class Validator
{
    protected array $data = [];
    protected array $errors = [];
    protected array $messages = [];
    protected array $compiledRules = [];

    /**
     * Optional PDO (DI support)
     * If null → fallback to DB::conn() (fast path)
     */
    protected ?PDO $pdo = null;

    /**
     * Rule → method map (no method_exists calls)
     */
    protected static array $ruleMap = [
        'required'  => 'validateRequired',
        'string'    => 'validateString',
        'alpha'     => 'validateAlpha',
        'alpha_num' => 'validateAlphaNum',
        'boolean'   => 'validateBoolean',
        'email'     => 'validateEmail',
        'numeric'   => 'validateNumeric',
        'integer'   => 'validateInteger',
        'array'     => 'validateArray',
        'min'       => 'validateMin',
        'max'       => 'validateMax',
        'between'   => 'validateBetween',
        'in'        => 'validateIn',
        'not_in'    => 'validateNotIn',
        'url'       => 'validateUrl',
        'ip'        => 'validateIp',
        'date'      => 'validateDate',
        'regex'     => 'validateRegex',
        'same'      => 'validateSame',
        'unique'    => 'validateUnique',
        'exists'    => 'validateExists',
        'phone'     => 'validatePhone',
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Resolve PDO (ZERO overhead fallback)
     */
    protected function pdo(): PDO
    {
        return $this->pdo ??= DB::conn();
    }

    /**
     * Main validation
     */
    public function validate(array $data, array $rules, array $messages = [], bool $isUpdate = false): bool
    {
        $this->data = $data;
        $this->messages = $messages;
        $this->errors = [];

        $this->compiledRules = $this->compileRules($rules);

        foreach ($this->compiledRules as $field => $rules) {

            if ($isUpdate && !array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field] ?? null;

            foreach ($rules as [$method, $rule, $param]) {

                if (!$this->$method($field, $value, $param, $isUpdate)) {
                    $this->addError($field, $rule, $param);
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Compile rules once (fast runtime)
     */
    protected function compileRules(array $rules): array
    {
        $compiled = [];

        foreach ($rules as $field => $ruleSet) {

            $ruleSet = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);

            foreach ($ruleSet as $rule) {

                $parts = explode(':', $rule, 2);
                $name = $parts[0];
                $param = $parts[1] ?? null;

                if (!isset(self::$ruleMap[$name])) {
                    throw new InvalidArgumentException("Rule [$name] not supported.");
                }

                $compiled[$field][] = [
                    self::$ruleMap[$name],
                    $name,
                    $param
                ];
            }
        }

        return $compiled;
    }

    protected function addError(string $field, string $rule, ?string $param): void
    {
        $this->errors[$field][] =
            $this->messages["$field.$rule"]
            ?? "$field validation failed ($rule)";
    }

    /**
     * Ultra-fast strlen (ASCII optimized)
     */
    protected function strlen(string $value): int
    {
        return mb_check_encoding($value, 'ASCII')
            ? strlen($value)
            : mb_strlen($value, 'UTF-8');
    }

    // =====================================================
    // CORE RULES
    // =====================================================

    protected function validateRequired($f, $v): bool
    {
        return $v !== null && $v !== '';
    }

    protected function validateString($f, $v): bool
    {
        return $v === null || is_string($v);
    }

    protected function validateAlpha($f, $v): bool
    {
        return $v === null || ctype_alpha($v);
    }

    protected function validateAlphaNum($f, $v): bool
    {
        return $v === null || ctype_alnum($v);
    }

    protected function validateBoolean($f, $v): bool
    {
        return in_array($v, [true, false, 0, 1, '0', '1'], true);
    }

    protected function validateEmail($f, $v): bool
    {
        return $v === null || filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateNumeric($f, $v): bool
    {
        return $v === null || is_numeric($v);
    }

    protected function validateInteger($f, $v): bool
    {
        return $v === null || filter_var($v, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateArray($f, $v): bool
    {
        return is_array($v);
    }

    protected function validateMin($f, $v, $p): bool
    {
        return $v === null || $this->strlen((string)$v) >= (int)$p;
    }

    protected function validateMax($f, $v, $p): bool
    {
        return $v === null || $this->strlen((string)$v) <= (int)$p;
    }

    protected function validateBetween($f, $v, $p): bool
    {
        if ($v === null || !$p) return true;

        [$min, $max] = explode(',', $p);

        $len = $this->strlen((string)$v);

        return $len >= (int)$min && $len <= (int)$max;
    }

    protected function validateIn($f, $v, $p): bool
    {
        return $v === null || in_array($v, explode(',', (string)$p), true);
    }

    protected function validateNotIn($f, $v, $p): bool
    {
        return $v === null || !in_array($v, explode(',', (string)$p), true);
    }

    protected function validateUrl($f, $v): bool
    {
        return $v === null || filter_var($v, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateIp($f, $v): bool
    {
        return $v === null || filter_var($v, FILTER_VALIDATE_IP) !== false;
    }

    protected function validateDate($f, $v): bool
    {
        return $v === null || strtotime($v) !== false;
    }

    protected function validateRegex($f, $v, $p): bool
    {
        return $v === null || ($p && preg_match($p, $v) === 1);
    }

    protected function validateSame($f, $v, $p): bool
    {
        return $v === null || ($this->data[$p] ?? null) === $v;
    }

    protected function validatePhone($f, $v): bool
    {
        if ($v === null) return true;

        return preg_match('/^[0-9\-\+\s\(\)]+$/', $v) === 1;
    }

    // =====================================================
    // DATABASE RULES (HYBRID)
    // =====================================================

    protected function validateUnique($f, $v, $p, $isUpdate = false): bool
    {
        if (!$p || $v === null || $v === '') return true;

        $pdo = $this->pdo();

        [$table, $column, $pk, $ignore] = array_pad(explode(',', $p), 4, null);

        $column = $column ?: $f;
        $pk = $pk ?: 'id';

        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
        $bind = [$v];

        if ($isUpdate && $ignore !== null) {
            $sql .= " AND `$pk` != ?";
            $bind[] = $ignore;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);

        return $stmt->fetchColumn() == 0;
    }

    protected function validateExists($f, $v, $p): bool
    {
        if (!$p) return false;

        $pdo = $this->pdo();

        [$table, $column] = explode(',', $p) + [null, null];

        $column = $column ?: $f;

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?"
        );

        $stmt->execute([$v]);

        return $stmt->fetchColumn() > 0;
    }

    // =====================================================
    // RESULTS
    // =====================================================

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }
}
