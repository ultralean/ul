<?php

namespace UltraLean\Core;

use InvalidArgumentException;
use PDO;

class Validator
{
    protected array $data = [];
    protected array $errors = [];
    protected array $messages = [];
    protected array $compiledRules = [];

    protected ?PDO $pdo = null;

    /**
     * Enable / disable UTF-8 mode
     */
    protected bool $utf8 = true;

    /**
     * Cache validated identifiers
     */
    protected static array $identifierCache = [];

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

    public function __construct(?PDO $pdo = null, bool $utf8 = true)
    {
        $this->pdo = $pdo;
        $this->utf8 = $utf8;
    }

    protected function pdo(): PDO
    {
        return $this->pdo ??= DB::conn();
    }

    // =====================================================
    // MAIN VALIDATION
    // =====================================================

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
                    $this->addError($field, $rule);
                }
            }
        }

        return empty($this->errors);
    }

    // =====================================================
    // RULE COMPILATION
    // =====================================================

    protected function compileRules(array $rules): array
    {
        $compiled = [];

        foreach ($rules as $field => $ruleSet) {

            $ruleSet = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);

            foreach ($ruleSet as $rule) {

                [$name, $param] = explode(':', $rule, 2) + [null, null];

                if (!isset(self::$ruleMap[$name])) {
                    throw new InvalidArgumentException("Rule [$name] not supported.");
                }

                if ($name === 'unique' || $name === 'exists') {
                    $param = $this->compileDbRule($field, $name, $param);
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

    protected function compileDbRule(string $field, string $rule, ?string $param): array
    {
        if (!$param) {
            throw new InvalidArgumentException("$rule requires parameters.");
        }

        $parts = explode(',', $param);

        $table  = self::assertIdentifier($parts[0]);
        $column = isset($parts[1]) ? self::assertIdentifier($parts[1]) : $field;

        if ($rule === 'exists') {
            return [$table, $column];
        }

        $pk     = isset($parts[2]) ? self::assertIdentifier($parts[2]) : 'id';
        $ignore = $parts[3] ?? null;

        return [$table, $column, $pk, $ignore];
    }

    protected static function assertIdentifier(string $value): string
    {
        return self::$identifierCache[$value]
            ??= (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)
                ? $value
                : throw new InvalidArgumentException("Invalid identifier [$value]"));
    }

    protected function addError(string $field, string $rule): void
    {
        $this->errors[$field][] =
            $this->messages["$field.$rule"]
            ?? "$field validation failed ($rule)";
    }

    // =====================================================
    // UTF-AWARE HELPERS
    // =====================================================

    protected function strlen(string $value): int
    {
        return $this->utf8
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }

    protected function isAlpha(string $value): bool
    {
        return $this->utf8
            ? preg_match('/^\p{L}+$/u', $value) === 1
            : ctype_alpha($value);
    }

    protected function isAlphaNum(string $value): bool
    {
        return $this->utf8
            ? preg_match('/^[\p{L}\p{N}]+$/u', $value) === 1
            : ctype_alnum($value);
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
        return $v === null || $this->isAlpha($v);
    }

    protected function validateAlphaNum($f, $v): bool
    {
        return $v === null || $this->isAlphaNum($v);
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
    // DATABASE RULES
    // =====================================================

    protected function validateUnique($f, $v, $p, $isUpdate = false): bool
    {
        if ($v === null || $v === '') return true;

        [$table, $column, $pk, $ignore] = $p;

        $sql = "SELECT 1 FROM `$table` WHERE `$column` = ?";
        $bind = [$v];

        if ($isUpdate && $ignore !== null) {
            $sql .= " AND `$pk` != ?";
            $bind[] = $ignore;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($bind);

        return $stmt->fetchColumn() === false;
    }

    protected function validateExists($f, $v, $p): bool
    {
        [$table, $column] = $p;

        $stmt = $this->pdo()->prepare(
            "SELECT 1 FROM `$table` WHERE `$column` = ? LIMIT 1"
        );

        $stmt->execute([$v]);

        return $stmt->fetchColumn() !== false;
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
