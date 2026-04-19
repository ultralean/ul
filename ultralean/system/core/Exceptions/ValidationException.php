<?php

namespace UltraLean\Core\Exceptions;

class ValidationException extends HttpException
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
